<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class GetDoneApiTest extends TestCase
{
    use RefreshDatabase;

    private function newUser(string $email): array
    {
        $res = $this->postJson('/api/auth/register', ['full_name'=>'Test User','email'=>$email,'password'=>'StrongPass123']);
        $res->assertCreated()->assertJsonPath('success',true);
        return [$res->json('data.access_token'), $res->json('data.user.id')];
    }
    public function test_auth_course_task_scoping_and_logout(): void
    {
        [$a] = $this->newUser('a@example.com');
        [$b] = $this->newUser('b@example.com');
        $this->withToken($a);
        $created = $this->postJson('/api/courses',['code'=>'CS101','title'=>'Web Dev','credits'=>3,'semester_status'=>'current']);
        $created->assertCreated(); $course = $created->json('data.id');
        $task = $this->postJson('/api/tasks',['course_id'=>$course,'title'=>'API task','due_date'=>now()->addDay()->toISOString(),'status'=>'todo','priority'=>'high']);
        $task->assertCreated(); $taskId = $task->json('data.id');
        $this->withToken($b);
        $this->getJson('/api/courses/'.$course)->assertNotFound();
        $this->putJson('/api/tasks/'.$taskId,['course_id'=>$course,'title'=>'Other user','due_date'=>now()->toISOString(),'status'=>'todo','priority'=>'medium'])->assertNotFound();
        $this->postJson('/api/tasks',['course_id'=>$course,'title'=>'Wrong owner','due_date'=>now()->toISOString(),'status'=>'todo','priority'=>'low'])->assertUnprocessable();
        $this->getJson('/api/courses')->assertJsonCount(0,'data');
        $this->withToken($a);
        $this->postJson('/api/auth/logout')->assertOk();
        $this->getJson('/api/users/me')->assertUnauthorized();
    }
    public function test_failed_login_and_input_validation(): void
    {
        $this->postJson('/api/auth/register',['full_name'=>'A','email'=>'bad','password'=>'123'])->assertUnprocessable();
        $this->newUser('first@example.com');
        $this->postJson('/api/auth/login',['email'=>'first@example.com','password'=>'bad'])->assertUnprocessable();
    }
}
