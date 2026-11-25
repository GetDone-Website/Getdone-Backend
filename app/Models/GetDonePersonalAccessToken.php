<?php
namespace App\Models;
use Laravel\Sanctum\PersonalAccessToken;
class GetDonePersonalAccessToken extends PersonalAccessToken
{
    protected $table = 'getdone_personal_access_tokens';
}
