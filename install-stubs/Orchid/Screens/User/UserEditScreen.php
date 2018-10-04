<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use Orchid\Screen\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Layouts;
use Illuminate\Http\Request;
use Orchid\Platform\Models\Role;
use Orchid\Platform\Models\User;
use Orchid\Support\Facades\Alert;
use Illuminate\Support\Facades\Auth;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserRoleLayout;
use App\Orchid\Layouts\User\UserChangePasswordLayout;

class UserEditScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'platform::systems/users.title';

    /**
     * Display header description.
     *
     * @var string
     */
    public $description = 'platform::systems/users.description';

    /**
     * Query data.
     *
     * @param \Orchid\Platform\Models\User $user
     *
     * @return array
     */
    public function query(User $user): array
    {
        return [
            'user'       => $user,
            'permission' => $user->getStatusPermission(),
            'roles'      => $user->getStatusRoles(),
        ];
    }

    /**
     * Button commands.
     *
     * @return array
     */
    public function commandBar(): array
    {
        return [
            Link::name('Войти от имени пользователя')
                ->icon('icon-login')
                ->method('switchUserStart'),

            Link::name('Change Password')
                ->icon('icon-lock-open')
                ->title('Change Password')
                ->modal('password'),

            Link::name(trans('platform::common.commands.save'))
                ->icon('icon-check')
                ->method('save'),

            Link::name(trans('platform::common.commands.remove'))
                ->icon('icon-trash')
                ->method('remove'),
        ];
    }

    /**
     * Views.
     *
     * @return array
     */
    public function layout(): array
    {
        return [
            UserEditLayout::class,
            UserRoleLayout::class,

            Layouts::modals([
               'password' => UserChangePasswordLayout::class,
            ]),
        ];
    }

    /**
     * @param \Orchid\Platform\Models\User $user
     * @param \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(User $user, Request $request)
    {
        $permissions = $request->get('permissions', []);
        $roles = Role::whereIn('slug', $request->get('roles', []))->get();

        foreach ($permissions as $key => $value) {
            unset($permissions[$key]);
            $permissions[base64_decode($key)] = $value;
        }

        $user
            ->fill($request->all())
            ->fill([
                'permissions' => $permissions,
            ])
            ->replaceRoles($roles)
            ->save();

        Alert::info(trans('platform::systems/users.User was saved'));

        return redirect()->route('platform.systems.users');
    }

    /**
     * @param $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove($id)
    {
        $user = User::findOrNew($id);

        $user->delete();

        Alert::info(trans('platform::systems/users.User was removed'));

        return redirect()->route('platform.systems.users');
    }

    /**
     * @param \Orchid\Platform\Models\User $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switchUserStart(User $user, Request $request)
    {
        if (! session()->has('original_user')) {
            session()->put('original_user', $request->user()->id);
        }
        Auth::login($user);

        return back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switchUserStop()
    {
        $id = session()->pull('original_user');
        Auth::loginUsingId($id);

        return back();
    }
}