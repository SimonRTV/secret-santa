<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * @var list<string>
     */
    private const array ADMIN_THEMES = ['neutral', 'ocean', 'forest'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $usesAdminTheme = $user instanceof User
            && ! $request->routeIs('home', 'content.*', 'families.*', 'members.*');

        $adminTheme = $usesAdminTheme && in_array($user->admin_theme, self::ADMIN_THEMES, true)
            ? $user->admin_theme
            : 'neutral';
        View::share('adminTheme', $adminTheme);

        return $next($request);
    }
}
