<?php

namespace Tests\Feature;

use App\Enums\ThreadScope;
use App\Livewire\Admin\Moderation;
use App\Livewire\Forum\ThreadCreate;
use App\Livewire\Forum\ThreadShow;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\Material;
use App\Models\User;
use App\Notifications\ThreadReplied;
use App\Services\Community\ForumService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->create(['forum_guidelines_accepted_at' => now()]);
    }

    public function test_first_post_requires_accepting_guidelines(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ThreadCreate::class)
            ->set('title', 'Cara cepat mengerjakan deret?')
            ->set('body', 'Saya selalu kehabisan waktu di soal deret angka.')
            ->call('save')
            ->assertHasErrors('guidelines');

        Livewire::actingAs($user)->test(ThreadCreate::class)
            ->set('title', 'Cara cepat mengerjakan deret?')
            ->set('body', 'Saya selalu kehabisan waktu di soal deret angka.')
            ->set('acceptGuidelines', true)
            ->call('save')
            ->assertRedirect();

        $this->assertSame(ThreadScope::Global, ForumThread::query()->sole()->scope);
        $this->assertTrue($user->fresh()->hasAcceptedForumGuidelines());
    }

    public function test_reply_notifies_thread_author_and_updates_counts(): void
    {
        Notification::fake();
        $author = $this->member();
        $thread = ForumThread::factory()->for($author, 'author')->create();

        Livewire::actingAs($this->member())->test(ThreadShow::class, ['thread' => $thread])
            ->set('body', 'Coba cari selisih bertingkat dulu.')
            ->call('reply')
            ->assertHasNoErrors();

        $this->assertSame(1, $thread->refresh()->replies_count);
        Notification::assertSentTo($author, ThreadReplied::class);
    }

    public function test_votes_toggle_and_self_votes_are_rejected(): void
    {
        $thread = ForumThread::factory()->create();
        $voter = $this->member();
        $forum = app(ForumService::class);

        $this->assertSame(1, $forum->toggleVote($voter, $thread));
        $this->assertSame(0, $forum->toggleVote($voter, $thread));

        $this->expectException(ValidationException::class);
        $forum->toggleVote($thread->author, $thread);
    }

    public function test_best_answer_awards_points_once(): void
    {
        $thread = ForumThread::factory()->create();
        $reply = ForumReply::factory()->for($thread, 'thread')->create();
        $forum = app(ForumService::class);

        $forum->markBest($thread->author, $thread, $reply);
        $forum->markBest($thread->author, $thread->refresh(), $reply); // unmark
        $forum->markBest($thread->author, $thread->refresh(), $reply); // mark again

        $this->assertSame($reply->id, $thread->refresh()->best_reply_id);
        $this->assertSame(config('talentra.points.best_answer'), (int) $reply->author->points()->sum('points'));
    }

    public function test_only_thread_author_can_mark_best_answer(): void
    {
        $thread = ForumThread::factory()->create();
        $reply = ForumReply::factory()->for($thread, 'thread')->create();

        $this->expectException(AuthorizationException::class);
        app(ForumService::class)->markBest($this->member(), $thread, $reply);
    }

    public function test_blocked_words_and_banned_users_cannot_post(): void
    {
        $forum = app(ForumService::class);
        $thread = ForumThread::factory()->create();

        try {
            $forum->reply($this->member(), $thread, 'dasar goblok kamu');
            $this->fail('Kata terlarang seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertSame(0, $thread->refresh()->replies_count);
        }

        $banned = User::factory()->create(['forum_guidelines_accepted_at' => now(), 'forum_banned_until' => now()->addDay()]);
        $this->expectException(ValidationException::class);
        $forum->reply($banned, $thread, 'Halo semua');
    }

    public function test_paid_material_discussion_is_private_to_subscribers(): void
    {
        $material = Material::factory()->create();
        $owner = User::factory()->admin()->create(['forum_guidelines_accepted_at' => now()]);
        $thread = ForumThread::factory()->for($owner, 'author')->create([
            'scope' => ThreadScope::Material,
            'material_id' => $material->id,
            'category_id' => $material->categoryId(),
        ]);

        $this->actingAs($this->member())->get(route('forum.thread', $thread))->assertForbidden();
    }

    public function test_reported_reply_can_be_hidden_by_moderator(): void
    {
        $reply = ForumReply::factory()->create();
        $reply->thread->forceFill(['replies_count' => 1])->save();
        app(ForumService::class)->report($this->member(), $reply, 'Isinya spam promosi');

        Livewire::actingAs(User::factory()->contentAdmin()->create())
            ->test(Moderation::class)
            ->assertSee('Isinya spam promosi')
            ->call('hide', 'reply', $reply->id);

        $this->assertNotNull($reply->refresh()->hidden_at);
        $this->assertSame(0, $reply->thread->refresh()->replies_count);
        $this->assertSame(0, $reply->reports()->open()->count());
    }

    public function test_forum_pages_render(): void
    {
        $user = $this->member();
        $thread = ForumThread::factory()->create();
        ForumReply::factory()->for($thread, 'thread')->create();

        $this->actingAs($user)->get(route('forum.index'))->assertOk()->assertSee($thread->title);
        $this->get(route('forum.thread', $thread))->assertOk();
        $this->get(route('forum.create'))->assertOk();
        $this->get(route('notifications.index'))->assertOk();
    }
}
