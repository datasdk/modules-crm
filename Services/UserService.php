<?php

namespace Modules\Crm\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Crm\Events\UserActivated;
use Modules\Crm\Events\UserCreated;
use Modules\Crm\Events\UserDeleted;
use Modules\Crm\Events\UserUpdated;
use Modules\Email\Services\InviteService;
use Modules\Email\Services\VerificationService;

class UserService
{
    protected InviteService $inviteService;

    protected VerificationService $verificationService;

    public function __construct()
    {
        $this->inviteService = new InviteService();
        $this->verificationService = app(VerificationService::class);
    }

    public function create(array $params): User
    {
        $existing = User::findByEmail($params['email']);

        if ($existing) {
            return $existing->load("address", "contact", "roles");
        }

        $user = User::create($this->extractUserFields($params));

        if (!empty($params['password'])) {
            $this->setPassword($user, $params['password'], false);
        }

        $isActivated = !empty($params['email_verified']);

        if ($isActivated) {
            $user->email_verified_at = now();
            $user->save();
        }

        if (!empty($params['invite'])) {
            $this->inviteService->send($user);
        } elseif ($params['send_activation'] ?? true) {
            $this->verificationService->send($user);
        }

        if (!empty($params['categories'])) {
            $this->setCategories($user, $params['categories']);
        }

        if (!empty($params['role'])) {
            $this->setRole($user, $params['role']);
        }

        if (array_key_exists('image', $params)) {
            if ($params['image'] === null || $params['image'] === '') {
                $this->removeProfileImage($user);
            } else {
                $this->setProfileImage($user, $params['image']);
            }
        }

        if (!empty($params['address'])) {
            $this->setAddress($user, $params['address']);
        }

        if (!empty($params['contact'])) {
            $this->setContact($user, $params['contact']);
        }

        event(new UserCreated($user));

        if ($isActivated) {
            event(new UserActivated($user));
        }

        return $user;
    }

    public function update(User $user, array $params): User
    {
        $user->update($this->extractUserFields($params));

        if (!empty($params['password'])) {
            $this->setPassword($user, $params['password'], false);
        }

        if (isset($params['invite']) && boolval($params['invite'])) {
            $this->inviteService->send($user);
        } elseif (isset($params['send_activation']) && boolval($params['send_activation'])) {
            $this->verificationService->send($user);
        }

        if (!empty($params['email_verified'])) {
            $user->email_verified_at = now();
            $user->save();
        }

        if (!empty($params['categories'])) {
            $this->setCategories($user, $params['categories']);
        }

        if (!empty($params['role'])) {
            $this->setRole($user, $params['role']);
        }

        if (array_key_exists('image', $params)) {
            if ($params['image'] === null || $params['image'] === '') {
                $this->removeProfileImage($user);
            } else {
                $this->setProfileImage($user, $params['image']);
            }
        }

        if (!empty($params['address'])) {
            $this->setAddress($user, $params['address']);
        }

        if (!empty($params['contact'])) {
            $this->setContact($user, $params['contact']);
        }

        event(new UserUpdated($user));

        return $user->refresh();
    }

    public function delete($id): int
    {
        $ids = is_array($id) ? $id : explode(',', $id);

        if (User::count() <= count($ids)) {
            abort(400, "You can't delete the last user");
        }

        foreach ($ids as $userId) {
            if ($user = User::find($userId)) {
                event(new UserDeleted($user));
            }
        }

        return User::destroy($ids);
    }

    private function extractUserFields(array $params): array
    {
        return collect($params)->only([
            'first_name', 'middle_name', 'last_name', 'email', 'type',
        ])->toArray();
    }

    private function setCategories(User $user, array $categories): void
    {
        $user->categories()->sync($categories);
    }

    private function setContact(User $user, array $data): void
    {
        $user->setContact($data);
    }

    private function setPassword(User $user, string $password, bool $sendVerification = false): void
    {
        if (!$password) {
            return;
        }

        $user->setPassword($password);

        if ($sendVerification && !$user->isVerified()) {
            $this->verificationService->send($user);
        }
    }

    private function setRole(User $user, mixed $role): void
    {
        $user->roles()->sync([$role]);
    }

    private function setAddress(User $user, array $data): void
    {
        $user->setAddress($data);
    }

    private function setProfileImage(User $user, $file): ?string
    {
        if ($file instanceof UploadedFile) {
            $filename = 'profile-image-' . $user->id . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('profile-images');

            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $imageUrl = url('profile-images/' . $filename);
        } elseif (isset($file) && empty($file)) {
            $imageUrl = null;
        } else {
            return null;
        }

        $user->image = $imageUrl;
        $user->save();

        return $imageUrl;
    }

    private function removeProfileImage(User $user)
    {
        $user->image = null;
        $user->save();

        return $this;
    }
}
