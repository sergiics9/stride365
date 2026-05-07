import { ClubSummary } from './club.model';

export type MembershipKind = 'club' | 'socio';
export type MembershipRole = 'admin_club' | 'socio';
export type MembershipStatus = 'pending' | 'active' | 'cancelled' | 'grace' | 'inactive';

export interface Membership {
  id: number;
  club_id: number;
  club: ClubSummary | null;
  role: MembershipRole;
  is_guide: boolean;
  status: MembershipStatus;
  kind: MembershipKind;
  subscription_name: string | null;
  subscribed_at: string | null;
  current_period_end: string | null;
  ends_at: string | null;
}

export interface MembershipsResponse {
  memberships: Membership[];
  has_admin_membership: boolean;
}

export interface SubscriptionStatus {
  name: string;
  kind: MembershipKind | null;
  club_id: number | null;
  subscribed: boolean;
  on_trial: boolean;
  on_grace_period: boolean;
  cancelled: boolean;
  ends_at: string | null;
  current_period_end: string | null;
  stripe_status: string | null;
  stripe_price: string | null;
}

export interface CheckoutRequest {
  kind: MembershipKind;
  club_id: number;
  success_url: string;
  cancel_url: string;
}

export interface CheckoutSession {
  id: string;
  url: string;
}

export interface CancelResumeRequest {
  kind: MembershipKind;
  club_id: number;
}

export interface InvoiceSubscriptionMeta {
  name: string;
  kind: MembershipKind | null;
  club_id: number | null;
}

export interface Invoice {
  id: string;
  number: string | null;
  total: string;
  date: string | null;
  download_url: string;
  subscription: InvoiceSubscriptionMeta | null;
}
