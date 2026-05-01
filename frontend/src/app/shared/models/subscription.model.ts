export interface SubscriptionStatus {
  subscribed: boolean;
  on_trial: boolean;
  on_grace_period: boolean;
  cancelled: boolean;
  ends_at: string | null;
  stripe_status: string | null;
  stripe_price: string | null;
}

export interface CheckoutSession {
  id: string;
  url: string;
}

export interface CheckoutRequest {
  price_id: string;
  success_url: string;
  cancel_url: string;
}

export interface Invoice {
  id: string;
  number: string | null;
  total: string;
  date: string | null;
  download_url: string;
}
