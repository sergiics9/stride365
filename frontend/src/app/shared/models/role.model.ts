export type RoleName = 'super_admin' | 'admin_club' | 'guia' | 'socio';

export interface Role {
  id: number;
  name: RoleName;
  guard_name: string;
  created_at?: string;
  updated_at?: string;
}
