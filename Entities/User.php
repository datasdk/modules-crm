<?php

namespace Modules\Crm\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken;
use DataSDK\Addresses\Models\Address;
use DataSDK\Addresses\Models\Contact;


class User extends Authenticatable
{
    // Use various traits for functionality like roles, categories, addresses, etc.
    use HasApiTokens, HasFactory, HasRoles;
 

    // Fillable attributes for mass assignment
    protected $fillable = [
        'uid',
        'username',
        'image',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'email_verified_at',
        'lastLoggedIn',
        'type'
    ];

    // Attributes to hide when converting to array or JSON
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'pivot',
        'lastLoggedIn',
    ];

    // Attributes that should be treated as dates
    protected $dates = [
        'lastLoggedIn',
    ];

    // Attributes to append to the model when converting to array or JSON
    protected $appends = [
        'lastLogin', 
        'online',
        'email_verified',
        'phone'
    ];



    // Password variable for later use
    public static $password = null;

    // Get the model's morph class (used for polymorphic relationships)
    public function getMorphClass()
    {
        return self::class;
    }

 
    public function getEmailVerifiedAttribute(){

        return $this->email_verified_at !== null;

    }

    // Get the last login time as a human-readable string
    public function getLastLoginAttribute()
    {
        return optional($this->lastLoggedIn)->diffForHumans();
    }


     // Get the last login time as a human-readable string
    public function getPhoneAttribute()
    {
        return optional($this->contacts()->first())->phone;
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable')->withDefault();
    }

    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function contact()
    {
        return $this->morphOne(Contact::class, 'contactable')->withDefault([
            'email' => $this->email,
        ]);
    }

    public function setAddress(array $data)
    {
        $this->addresses()->updateOrCreate(
            ['is_primary' => true],
            array_merge($data, ['is_primary' => true])
        );

        return $this;
    }

    public function setContact(array $data)
    {
        $this->contacts()->updateOrCreate(
            ['is_primary' => true],
            array_merge($data, ['is_primary' => true])
        );

        return $this;
    }

    // Check if the user is online
    public function getOnlineAttribute(){
        //return $this->isOnline();
    }

    // Set the user's online status
    public function setOnline($online = true){

        if($online){ 
            
            $this->setCache(config('session.lifetime') * 60); 
        
        } 
        else{ 
            
            $this->setOffline(); 
        
        }

        
        return $this;
    }

    // Set the user's offline status
    public function setOffline(){
        return $this->pullCache();
    }

    // Mutator for the email field (convert email to lowercase before saving)
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    // Get the currently authenticated user
    public static function me()
    {
        return Auth::user();
    }

    // Generate and set a random password with the specified number of characters
    public function setRandomPassword(int $numberOfCharacters)
    {
        $password = strtolower(Str::random($numberOfCharacters));
        return $this->setPassword($password);
    }

    // Set the user's password and hash it
    public function setPassword($password = null)
    {
        if (empty($password)) {
            if (empty($this->password)) {
                $password = uniqid();
            } else {
                return $this;
            }
        }

        self::$password = $password;
        $this->password = Hash::make($password);
        $this->save();

        return $this;
    }

    // Verify the user's email if not already verified
    public function verify()
    {
        if (!$this->isVerified()) {
            $this->email_verified_at = now();
            $this->save();
        }

        return $this;
    }

    // Check if the user's email is verified
    public function isVerified()
    {
        return $this->email_verified_at !== null;
    }

    // Get the current password (for testing purposes)
    public function getPassword()
    {
        return self::$password;
    }

    // Set the user's role by role ID
    public function setRole($id)
    {
        if (!empty($id)) {
            $this->syncRoles(Role::find($id));
        }
        return $this;
    }

    // Find a user by their email address
    public static function findByEmail($email)
    {
        return self::where("email", $email)->first();
    }

    // Check if the user is an admin (admin is considered a moderator here)
    public function isAdmin()
    {
        return $this->isModerator();
    }

    // Check if the user is a normal user (not a moderator)
    public function isUser()
    {
        return !$this->isModerator();
    }

    // Check if the user is a moderator
    public function isModerator()
    {
        return $this->hasAnyRole(
            ['admin', 'editor', 'analyzer'], // roller
                                 // guard
        );

    }



}
