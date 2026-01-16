<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Member;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RolesPermissionsAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        DB::beginTransaction();

        try {
            // ==========================================
            // 1. CREATE PERMISSIONS
            // ==========================================
            $this->createPermissions();

            // ==========================================
            // 2. CREATE ROLES AND ASSIGN PERMISSIONS
            // ==========================================
            $roles = $this->createRoles();

            // ==========================================
            // 3. CREATE USERS WITH PROFILES
            // ==========================================
            $this->createUsers($roles);

            DB::commit();

            $this->command->info('✅ Roles, permissions, and users created successfully!');
            $this->displayCredentials();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeder failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create all permissions.
     */
    private function createPermissions(): void
    {
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Book Management
            'view books',
            'create books',
            'edit books',
            'delete books',
            
            // Book Copy Management
            'view book copies',
            'create book copies',
            'edit book copies',
            'delete book copies',
            
            // Loan Management
            'view loans',
            'create loans',
            'return loans',
            'delete loans',
            
            // Category Management
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Genre Management
            'view genres',
            'create genres',
            'edit genres',
            'delete genres',
            
            // Publisher Management
            'view publishers',
            'create publishers',
            'edit publishers',
            'delete publishers',
            
            // Fine Management
            'view fines',
            'waive fines',
            'collect fines',
            
            // Reports
            'view reports',
            'export reports',
            
            // System Settings
            'manage settings',
            'manage roles',
            'manage permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('✅ Permissions created');
    }

    /**
     * Create roles and assign permissions.
     */
    private function createRoles(): array
    {
        // 1. Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // 2. Admin - Almost full access (no system settings)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view users', 'create users', 'edit users', 'delete users',
            'view books', 'create books', 'edit books', 'delete books',
            'view book copies', 'create book copies', 'edit book copies', 'delete book copies',
            'view loans', 'create loans', 'return loans', 'delete loans',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view genres', 'create genres', 'edit genres', 'delete genres',
            'view publishers', 'create publishers', 'edit publishers', 'delete publishers',
            'view fines', 'waive fines', 'collect fines',
            'view reports', 'export reports',
        ]);

        // 3. Librarian - Book and loan management
        $librarian = Role::firstOrCreate(['name' => 'librarian']);
        $librarian->givePermissionTo([
            'view users',
            'view books', 'create books', 'edit books',
            'view book copies', 'create book copies', 'edit book copies',
            'view loans', 'create loans', 'return loans',
            'view categories', 'create categories',
            'view genres', 'create genres',
            'view publishers', 'create publishers',
            'view fines', 'collect fines',
            'view reports',
        ]);

        // 4. Member - Basic user (borrower)
        $member = Role::firstOrCreate(['name' => 'member']);
        $member->givePermissionTo([
            'view books',
            'view book copies',
        ]);

        $this->command->info('✅ Roles created and permissions assigned');

        return compact('superAdmin', 'admin', 'librarian', 'member');
    }

    /**
     * Create default users with their profiles.
     */
    private function createUsers(array $roles): void
    {
        // ==========================================
        // SUPER ADMIN
        // ==========================================
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@library.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'phone' => '+1-555-0001',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $superAdminUser->syncRoles(['super-admin']);
        
        // Create staff profile
        if (!$superAdminUser->staff) {
            $superAdminUser->staff()->create([
                'department' => 'Executive',
                'position' => 'System Administrator',
                'hire_date' => now()->subYears(5),
                'work_hours' => [
                    'monday' => '8:00-18:00',
                    'tuesday' => '8:00-18:00',
                    'wednesday' => '8:00-18:00',
                    'thursday' => '8:00-18:00',
                    'friday' => '8:00-18:00',
                ],
            ]);
        }

        // ==========================================
        // ADMIN
        // ==========================================
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@library.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '+1-555-0002',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['admin']);
        
        // Create staff profile
        if (!$adminUser->staff) {
            $adminUser->staff()->create([
                'department' => 'Administration',
                'position' => 'Library Administrator',
                'hire_date' => now()->subYears(3),
                'work_hours' => [
                    'monday' => '9:00-17:00',
                    'tuesday' => '9:00-17:00',
                    'wednesday' => '9:00-17:00',
                    'thursday' => '9:00-17:00',
                    'friday' => '9:00-17:00',
                ],
            ]);
        }

        // ==========================================
        // LIBRARIANS (3 users)
        // ==========================================
        $librarians = [
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@library.com',
                'phone' => '+1-555-0003',
                'department' => 'Circulation',
                'position' => 'Senior Librarian',
                'hire_date' => now()->subYears(2),
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael@library.com',
                'phone' => '+1-555-0004',
                'department' => 'Reference',
                'position' => 'Reference Librarian',
                'hire_date' => now()->subYear(),
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily@library.com',
                'phone' => '+1-555-0005',
                'department' => 'Children Section',
                'position' => 'Children\'s Librarian',
                'hire_date' => now()->subMonths(8),
            ],
        ];

        foreach ($librarians as $librarianData) {
            $librarian = User::firstOrCreate(
                ['email' => $librarianData['email']],
                [
                    'name' => $librarianData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $librarianData['phone'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $librarian->syncRoles(['librarian']);
            
            if (!$librarian->staff) {
                $librarian->staff()->create([
                    'department' => $librarianData['department'],
                    'position' => $librarianData['position'],
                    'hire_date' => $librarianData['hire_date'],
                    'work_hours' => [
                        'monday' => '9:00-17:00',
                        'tuesday' => '9:00-17:00',
                        'wednesday' => '9:00-17:00',
                        'thursday' => '9:00-17:00',
                        'friday' => '9:00-17:00',
                        'saturday' => '10:00-14:00',
                    ],
                ]);
            }
        }

        // ==========================================
        // MEMBERS (5 users)
        // ==========================================
        $members = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1-555-1001',
                'dob' => '1990-05-15',
                'gender' => 'male',
                'address' => '123 Main Street, Springfield, IL 62701',
                'membership_type' => 'standard',
                'emergency_name' => 'Jane Smith',
                'emergency_phone' => '+1-555-1002',
                'emergency_relationship' => 'Spouse',
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria.garcia@example.com',
                'phone' => '+1-555-1003',
                'dob' => '1985-08-22',
                'gender' => 'female',
                'address' => '456 Oak Avenue, Springfield, IL 62702',
                'membership_type' => 'premium',
                'emergency_name' => 'Carlos Garcia',
                'emergency_phone' => '+1-555-1004',
                'emergency_relationship' => 'Brother',
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david.wilson@example.com',
                'phone' => '+1-555-1005',
                'dob' => '2000-03-10',
                'gender' => 'male',
                'address' => '789 Elm Street, Springfield, IL 62703',
                'membership_type' => 'student',
                'emergency_name' => 'Linda Wilson',
                'emergency_phone' => '+1-555-1006',
                'emergency_relationship' => 'Mother',
            ],
            [
                'name' => 'Patricia Brown',
                'email' => 'patricia.brown@example.com',
                'phone' => '+1-555-1007',
                'dob' => '1955-11-30',
                'gender' => 'female',
                'address' => '321 Pine Road, Springfield, IL 62704',
                'membership_type' => 'senior',
                'emergency_name' => 'Robert Brown',
                'emergency_phone' => '+1-555-1008',
                'emergency_relationship' => 'Son',
            ],
            [
                'name' => 'James Taylor',
                'email' => 'james.taylor@example.com',
                'phone' => '+1-555-1009',
                'dob' => '1995-07-18',
                'gender' => 'male',
                'address' => '654 Maple Drive, Springfield, IL 62705',
                'membership_type' => 'standard',
                'emergency_name' => 'Emma Taylor',
                'emergency_phone' => '+1-555-1010',
                'emergency_relationship' => 'Sister',
            ],
        ];

        foreach ($members as $memberData) {
            $member = User::firstOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $memberData['phone'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $member->syncRoles(['member']);
            
            if (!$member->member) {
                $member->member()->create([
                    'date_of_birth' => $memberData['dob'],
                    'gender' => $memberData['gender'],
                    'address' => $memberData['address'],
                    'membership_start_date' => now()->subMonths(rand(1, 12)),
                    'membership_expiry_date' => now()->addYear(),
                    'membership_type' => $memberData['membership_type'],
                    'emergency_contact_name' => $memberData['emergency_name'],
                    'emergency_contact_phone' => $memberData['emergency_phone'],
                    'emergency_contact_relationship' => $memberData['emergency_relationship'],
                    'max_books_allowed' => $memberData['membership_type'] === 'premium' ? 10 : 5,
                    'max_days_allowed' => 14,
                    'receive_notifications' => true,
                    'receive_newsletters' => true,
                ]);
            }
        }

        $this->command->info('✅ Users created with profiles');
    }

    /**
     * Display all credentials.
     */
    private function displayCredentials(): void
    {
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('                    DEFAULT USER CREDENTIALS                    ');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->newLine();

        $this->command->info('🛡️  SUPER ADMIN');
        $this->command->info('   Email: superadmin@library.com');
        $this->command->info('   Password: password');
        $this->command->newLine();

        $this->command->info('👨‍💼 ADMIN');
        $this->command->info('   Email: admin@library.com');
        $this->command->info('   Password: password');
        $this->command->newLine();

        $this->command->info('📚 LIBRARIANS');
        $this->command->info('   Email: sarah@library.com / Password: password');
        $this->command->info('   Email: michael@library.com / Password: password');
        $this->command->info('   Email: emily@library.com / Password: password');
        $this->command->newLine(); 

        $this->command->info('👥 MEMBERS');
        $this->command->info('   Email: john.smith@example.com / Password: password');
        $this->command->info('   Email: maria.garcia@example.com / Password: password');
        $this->command->info('   Email: david.wilson@example.com / Password: password');
        $this->command->info('   Email: patricia.brown@example.com / Password: password');
        $this->command->info('   Email: james.taylor@example.com / Password: password');
        $this->command->newLine();

        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->warn('⚠️  IMPORTANT: Change these passwords in production!');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}