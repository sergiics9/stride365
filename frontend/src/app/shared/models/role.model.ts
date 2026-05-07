export type RoleName = 'super_admin' | 'usuario';

export interface Role {
  id: number;
  name: RoleName;
  guard_name: string;
  created_at?: string;
  updated_at?: string;
}
