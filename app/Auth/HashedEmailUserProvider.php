<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class HashedEmailUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials, matching the email
     * against email_hash instead of the (now encrypted) email column.
     *
     * @param  array  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        if (isset($credentials['email'])) {
            return User::findByEmail($credentials['email']);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
