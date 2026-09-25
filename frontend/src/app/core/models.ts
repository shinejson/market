export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  role: 'super_admin' | 'tenant_owner' | 'store_staff' | 'customer';
  tenant_id: number | null;
  roles: { role: string; tenant_id: number | null; store_id: number | null }[];
}

export interface AuthResponse {
  token: string;
  user: User;
}

export interface ProductCard {
  id: number;
  name: string;
  slug: string;
  price: string;
  brand?: string | null;
  status: string;
  image?: string | null;
  images: { id: number; url: string; is_primary: boolean }[];
  store: { id: number; name: string; slug: string } | null;
  category: { id: number; name: string; slug: string } | null;
  variants: {
    id: number;
    sku: string;
    options: Record<string, unknown> | null;
    price: string;
    available: number;
    status: string;
  }[];
  description?: string;
  sponsored?: boolean;
  impression_id?: number;
}

export interface Storefront {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  city?: string | null;
  country?: string | null;
  delivery_fee: string | number;
  delivery_days: number;
  is_featured?: boolean;
}

export interface Paginated<T> {
  data: T;
  meta: { page: number; per_page: number; total: number; last_page: number };
}

export interface CartPayload {
  id: number;
  groups: {
    store: { id: number; name: string; slug: string; delivery_fee: string | number };
    items: {
      id: number;
      variant_id: number;
      sku: string;
      product_name: string;
      options: Record<string, unknown> | null;
      qty: number;
      unit_price: string;
      line_total: string;
      image?: string | null;
      available: number;
    }[];
    subtotal: string;
  }[];
  totals: {
    subtotal: string;
    delivery_total: string;
    tax_total: string;
    grand_total: string;
    currency: string;
  };
}

export interface Address {
  id: number;
  label?: string | null;
  full_name: string;
  phone?: string | null;
  line1: string;
  line2?: string | null;
  city: string;
  state?: string | null;
  postal_code?: string | null;
  country: string;
  is_default: boolean;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  parent_id?: number | null;
  children?: Category[];
}
