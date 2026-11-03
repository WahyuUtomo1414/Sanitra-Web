/**
 * Credentials match database/seeders/UserSeeder.php. Keep in sync if the
 * seeder's sample accounts change.
 */
export const ROLES = {
  superAdmin: {
    email: 'super@gmail.com',
    password: '12345678',
    storageStatePath: 'playwright/.auth/super-admin.json',
  },
  marketing: {
    email: 'marketing@gmail.com',
    password: '12345678',
    storageStatePath: 'playwright/.auth/marketing.json',
  },
  finance: {
    email: 'finance@gmail.com',
    password: '12345678',
    storageStatePath: 'playwright/.auth/finance.json',
  },
  teknis: {
    email: 'teknis@gmail.com',
    password: '12345678',
    storageStatePath: 'playwright/.auth/teknis.json',
  },
} as const;

export type RoleKey = keyof typeof ROLES;
