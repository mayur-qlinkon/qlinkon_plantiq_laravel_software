<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a realistic looking Indian GSTIN (e.g., 24ABCDE1234F1Z5)
        $stateCode = $this->faker->numberBetween(10, 37);
        $pan = strtoupper($this->faker->lexify('?????').$this->faker->numerify('####').$this->faker->lexify('?'));
        $fakeGstin = $stateCode.$pan.$this->faker->randomElement(['1', '2']).'Z'.$this->faker->randomElement(['1', '5', 'A', 'M']);

        $registrationType = $this->faker->randomElement(['registered', 'composition', 'unregistered', 'sez', 'overseas']);

        return [
            // Relationships (Will be overridden by the Seeder if passed explicitly)
            'company_id' => Company::factory(),

            'user_id' => null,  // Left null as per your "Future Bridge" note

            // Core Info
            'name' => $this->faker->name(),
            'client_code' => 'CLI-'.$this->faker->unique()->numerify('####'),
            'company_name' => $this->faker->boolean(70) ? $this->faker->company() : null, // 70% chance to have a company name
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('9#########'), // Standard 10-digit Indian mobile number

            // Compliance
            'registration_type' => $registrationType,
            'gst_number' => $registrationType !== 'unregistered' ? $fakeGstin : null,

            // Address
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            // Grab a random state ID if states table is populated, otherwise fallback to 24 (Gujarat)
            'state_id' => State::inRandomOrder()->value('id') ?? 24,
            'zip_code' => $this->faker->numerify('######'), // 6-digit Indian pin code
            'country' => 'India',

            // Extras
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'is_active' => $this->faker->boolean(90), // 90% chance to be active
        ];
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the client is a registered B2B business.
     */
    public function registered(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_type' => 'registered',
            'company_name' => $this->faker->company(),
            // Forces generation of a new GSTIN
            'gst_number' => $this->faker->numberBetween(10, 37).strtoupper($this->faker->lexify('?????').$this->faker->numerify('####').$this->faker->lexify('?')).'1Z5',
        ]);
    }

    /**
     * Indicate that the client is a simple B2C walk-in.
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_type' => 'unregistered',
            'company_name' => null,
            'gst_number' => null,
            'address' => null,
            'notes' => 'Walk-in customer',
        ]);
    }
}
