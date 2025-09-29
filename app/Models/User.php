<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\QuestionType;
use App\Models\Role;
use App\Models\EducationLevel;
use App\Models\Subcategory;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'profile_img',
        'preferred_industry_id',
        'role_id',
        'education_level_id',
        'linkedin_profile_url',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $appends = ['preferred_industry', 'role', 'education_level', 'preferred_industry_type'];

    public function getPreferredIndustryAttribute()
    {
        return $this->preferred_industry_id ? Subcategory::find($this->preferred_industry_id) : null;
    }
    public function getPreferredIndustryTypeAttribute()
    {
        if ($this->preferred_industry && isset($this->preferred_industry->questiontype_id)) {
            return QuestionType::find($this->preferred_industry->questiontype_id);
        }
        return null;
    }
    public function getRoleAttribute()
    {
        return $this->role_id ? Role::find($this->role_id) : null;
    }

    public function getEducationLevelAttribute()
    {
        return $this->education_level_id ? EducationLevel::find($this->education_level_id) : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
