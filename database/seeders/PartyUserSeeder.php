<?php

namespace Fpaipl\Brandy\Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Party;
use Illuminate\Database\Seeder;
use Fpaipl\Authy\Models\Address;
use Fpaipl\Brandy\Models\Employee;

class PartyUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Brand
            [
                'name' => 'Owner Brand',
                'email' => 'obrand@gmail.com',
                'role' => 'owner-brand',
            ],
            [
                'name' => 'Manager Brand',
                'email' => 'mbrand@gmail.com',
                'role' => 'manager-brand',
            ],
            [
                'name' => 'Brand OM',
                'email' => 'ombrand@gmail.com',
                'role' => 'order-manager-brand',
            ],
            [
                'name' => 'Brand SM',
                'email' => 'smbrand@gmail.com',
                'role' => 'store-manager-brand',
            ],
            [
                'name' => 'Brand AM',
                'email' => 'ambrand@gmail.com',
                'role' => 'account-manager-brand',
            ],
            // Vendor
            [
                'name' => 'Vendor 1',
                'email' => 'vendor1@gmail.com',
                // 'role' => 'owner-vendor',
                'role' => 'manager-vendor',
            ],
            [
                'name' => 'Vendor 2',
                'email' => 'vendor2@gmail.com',
                'role' => 'manager-vendor',
            ],
            [
                'name' => 'Vendor 3',
                'email' => 'vendor3@gmail.com',
                'role' => 'manager-vendor',
            ],
            // Factory
            [
                'name' => 'Factory A',
                'email' => 'factorya@gmail.com',
                'role' => 'manager-factory',
                // 'role' => 'owner-factory',
            ],
            [
                'name' => 'Factory B',
                'email' => 'factoryb@gmail.com',
                'role' => 'manager-factory',
            ],
            [
                'name' => 'Factory C',
                'email' => 'factoryc@gmail.com',
                'role' => 'manager-factory',
            ],
        ];

        foreach($users as $user){
            $newUser = User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'utype' => 'email',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'remember_token' => Str::random(10),
            ]);
            
            $newUser->assignRole($user['role']);
            
            if(Str::contains($user['role'], 'brand')) {

                $newUser->update([
                    'type' => 'user-brand',
                ]);

                Employee::create([
                    'user_id' => $newUser->id,
                    'name' => Str::title($user['name']),
                    'info' => 'He will be responsible for ' . $user['role'],
                ]);
            }

            if(Str::contains($user['role'], 'vendor')) {

                $newUser->update([
                    'type' => 'user-vendor',
                ]);

                $newParty = Party::create([
                    'user_id' => $newUser->id,
                    'business' => Str::title($user['name']) . ' business',
                    'type' => 'product-vendor',
                    'info' => 'He will be responsible for vendor',
                    'name' => Str::title($user['name']),
                    'mobile' => '98699' . rand(10000, 99999),
                ]);
    
                $address = Address::create([
                    'name' => Str::title($user['name']) . ' business',
                    'line1' => 'some line 1',
                    'line2' => 'line 2',
                    'pincode' => 142578,
                    'state' => 'delhi',
                    'country' => 'India',
                    'contacts' => '8754875487',
                    'addressable_id' => $newParty->id,
                    'addressable_type' => 'Fpaipl\Brandy\Models\Party',
                ]);
    
                $newParty->billing_address_id = $address->id;
                $newParty->shipping_address_id = $address->id;
                $newParty->saveQuietly();
            }

            if(Str::contains($user['role'], 'factory')) {

                $newUser->update([
                    'type' => 'user-factory',
                ]);

                $newParty = Party::create([
                    'user_id' => $newUser->id,
                    'business' => Str::title($user['name']) . ' business',
                    'type' => 'product-factory',
                    'info' => 'He will be responsible for factory',
                    'name' => Str::title($user['name']),
                    'mobile' => '98699' . rand(10000, 99999),
                ]);
    
                $address = Address::create([
                    'name' => Str::title($user['name']) . ' business',
                    'line1' => 'some line 1',
                    'line2' => 'line 2',
                    'pincode' => 142578,
                    'state' => 'delhi',
                    'country' => 'India',
                    'contacts' => '8754875487',
                    'addressable_id' => $newParty->id,
                    'addressable_type' => 'Fpaipl\Brandy\Models\Party',
                ]);
    
                $newParty->billing_address_id = $address->id;
                $newParty->shipping_address_id = $address->id;
                $newParty->saveQuietly();
            }

        }
    }
}
