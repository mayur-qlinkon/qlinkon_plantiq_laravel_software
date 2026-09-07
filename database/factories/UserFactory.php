<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\Auth\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'company_id' => 1, // existing company
            'user_type' => UserType::INTERNAL,

            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            
            'image' => fake()->optional()->imageUrl(),
            'address' => fake()->address(),
            'state_id' => null, // keep simple for now
            'country' => 'India',
            'zip_code' => fake()->postcode(),

            'status' => 'active',
        ];
    }
}