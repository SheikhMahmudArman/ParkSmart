<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class RawToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        abort_unless($token, 401, 'Please log in.');
        $user = DB::selectOne('SELECT u.id,u.name,u.email,u.phone,u.role,t.id AS token_id FROM api_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.expires_at>NOW()', [hash('sha256', $token)]);
        abort_unless($user, 401, 'Your login has expired. Please log in again.');
        $request->attributes->set('actor', $user);
        return $next($request);
    }
}
