<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'udhaarhisab:create-admin
                            {--name= : Full name of the super admin}
                            {--email= : Login email address}
                            {--phone= : Login mobile number (digits only)}
                            {--password= : Password (a random one is generated if omitted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a platform super admin account for UdhaarHisab';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name', 'Super Admin');
        $email = $this->option('email') ?: $this->ask('Email');
        $phone = $this->option('phone') ?: $this->ask('Mobile number');
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        $generated = false;
        $password = $this->option('password');
        if (!$password) {
            $password = Str::password(16, symbols: false);
            $generated = true;
        }

        $validator = Validator::make(
            compact('name', 'email', 'phone', 'password'),
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:150', 'unique:users,email'],
                'phone' => ['required', 'digits_between:10,15', 'unique:users,phone'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'tenant_id' => null,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => 'super_admin',
            'password' => $password,
            'is_active' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("Super admin created (ID {$user->id}).");
        $this->table(['Field', 'Value'], [
            ['Name', $user->name],
            ['Email', $user->email],
            ['Phone', $user->phone],
            ['Password', $generated ? $password : '(as provided)'],
        ]);

        if ($generated) {
            $this->warn('Save this password now — it will not be shown again.');
        }

        return self::SUCCESS;
    }
}
