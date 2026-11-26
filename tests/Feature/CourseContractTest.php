<?php
namespace Tests\Feature;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseContractTest extends TestCase
{
    use RefreshDatabase;

    private function newUser(string $email): array
    {
        $res = $this->postJson('/api/auth/register', ['full_name' => 'Test User', 'nim' => 'NIM' . abs(crc32($email)), 'email' => $email, 'password' => 'StrongPass123']);
        $res->assertCreated();
        return [$res->json('data.access_token'), $res->json('data.user.id')];
    }
    /** Berganti pengguna di tengah tes: guard Sanctum menyimpan pengguna pertama, jadi cache dibersihkan lalu identitas diverifikasi. */
    private function actAs(string $token, string $email): void
    {
        $this->app['auth']->forgetGuards();
        $this->withToken($token);
        $this->getJson('/api/users/me')->assertOk()->assertJsonPath('data.email', $email);
    }
    private function newCourse(array $extra = []): string
    {
        $res = $this->postJson('/api/courses', array_merge(['code' => 'CS101', 'title' => 'Web Dev', 'credits' => 3], $extra));
        $res->assertCreated();
        return $res->json('data.id');
    }
    private function upload(string $course, UploadedFile $file)
    {
        return $this->post('/api/courses/' . $course . '/contract', ['contract' => $file], ['Accept' => 'application/json']);
    }

    public function test_vision_and_mission_roundtrip(): void
    {
        [$a] = $this->newUser('a@example.com');
        $this->withToken($a);
        $id = $this->newCourse(['vision' => 'Visi satu paragraf', 'mission' => ['poin 1', 'poin 2']]);
        $this->getJson('/api/courses/' . $id)->assertOk()
            ->assertJsonPath('data.vision', 'Visi satu paragraf')
            ->assertJsonPath('data.mission', ['poin 1', 'poin 2'])
            ->assertJsonPath('data.contract', null);
        $plain = $this->newCourse(['code' => 'CS102', 'title' => 'Tanpa Misi']);
        $this->getJson('/api/courses/' . $plain)->assertJsonPath('data.mission', [])->assertJsonPath('data.vision', null);
    }

    public function test_mission_over_ten_items_rejected(): void
    {
        [$a] = $this->newUser('a@example.com');
        $this->withToken($a);
        $this->postJson('/api/courses', ['code' => 'CS101', 'title' => 'X', 'credits' => 3, 'mission' => array_map(fn ($i) => "poin $i", range(1, 11))])->assertUnprocessable();
        $this->postJson('/api/courses', ['code' => 'CS101', 'title' => 'X', 'credits' => 3, 'mission' => [str_repeat('a', 301)]])->assertUnprocessable();
    }

    public function test_upload_replace_download_delete_contract(): void
    {
        Storage::fake('local');
        [$a, $uid] = $this->newUser('a@example.com');
        $this->withToken($a);
        $id = $this->newCourse();

        $this->upload($id, UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf'))
            ->assertOk()->assertJsonPath('data.contract.name', 'kontrak.pdf')
            ->assertJsonMissingPath('data.contract_path');
        $first = "courses/contracts/$uid/$id.pdf";
        Storage::disk('local')->assertExists($first);

        $this->upload($id, UploadedFile::fake()->create('kontrak2.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
            ->assertOk()->assertJsonPath('data.contract.name', 'kontrak2.docx');
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists("courses/contracts/$uid/$id.docx");

        $this->get('/api/courses/' . $id . '/contract', ['Accept' => 'application/json'])->assertOk()->assertDownload('kontrak2.docx');

        $this->deleteJson('/api/courses/' . $id . '/contract')->assertOk()->assertJsonPath('data.contract', null);
        Storage::disk('local')->assertMissing("courses/contracts/$uid/$id.docx");
        $this->get('/api/courses/' . $id . '/contract', ['Accept' => 'application/json'])->assertNotFound();
    }

    public function test_contract_rejects_wrong_type_and_size(): void
    {
        Storage::fake('local');
        [$a] = $this->newUser('a@example.com');
        $this->withToken($a);
        $id = $this->newCourse();
        $this->upload($id, UploadedFile::fake()->image('foto.png'))->assertUnprocessable();
        $this->upload($id, UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'))->assertUnprocessable();
        $this->getJson('/api/courses/' . $id)->assertJsonPath('data.contract', null);
    }

    public function test_contract_other_user_gets_404(): void
    {
        Storage::fake('local');
        [$a] = $this->newUser('a@example.com');
        [$b] = $this->newUser('b@example.com');
        $this->withToken($a);
        $id = $this->newCourse();
        $this->upload($id, UploadedFile::fake()->create('kontrak.pdf', 10, 'application/pdf'))->assertOk();
        $this->actAs($b, 'b@example.com');
        $this->upload($id, UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))->assertNotFound();
        $this->get('/api/courses/' . $id . '/contract', ['Accept' => 'application/json'])->assertNotFound();
        $this->deleteJson('/api/courses/' . $id . '/contract')->assertNotFound();
    }

    public function test_deleting_course_removes_contract_file(): void
    {
        Storage::fake('local');
        [$a, $uid] = $this->newUser('a@example.com');
        $this->withToken($a);
        $id = $this->newCourse();
        $this->upload($id, UploadedFile::fake()->create('kontrak.pdf', 10, 'application/pdf'))->assertOk();
        Storage::disk('local')->assertExists("courses/contracts/$uid/$id.pdf");
        $this->deleteJson('/api/courses/' . $id)->assertOk();
        Storage::disk('local')->assertMissing("courses/contracts/$uid/$id.pdf");
    }

    public function test_updating_course_keeps_contract(): void
    {
        Storage::fake('local');
        [$a] = $this->newUser('a@example.com');
        $this->withToken($a);
        $id = $this->newCourse();
        $this->upload($id, UploadedFile::fake()->create('kontrak.pdf', 10, 'application/pdf'))->assertOk();
        $this->putJson('/api/courses/' . $id, ['code' => 'CS101', 'title' => 'Diubah', 'credits' => 3, 'vision' => 'v', 'mission' => ['a']])->assertOk()
            ->assertJsonPath('data.contract.name', 'kontrak.pdf')->assertJsonPath('data.title', 'Diubah');
    }

    public function test_analytics_returns_sessions_from_last_35_days(): void
    {
        [$a, $uid] = $this->newUser('a@example.com');
        $user = User::findOrFail($uid);
        $user->focusSessions()->create(['task_title' => 'lama', 'duration_minutes' => 25, 'completed_at' => now()->subDays(40)]);
        for ($i = 0; $i < 120; $i++) {
            $user->focusSessions()->create(['task_title' => "s$i", 'duration_minutes' => 25, 'completed_at' => now()->subDays(34)->addMinutes($i)]);
        }
        $this->withToken($a);
        $rows = $this->getJson('/api/analytics')->assertOk()->json('data.focus_sessions');
        $this->assertCount(120, $rows);
        $this->assertNotContains('lama', array_column($rows, 'task_title'));
    }
}
