import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Address, CartPayload, Category, Paginated, ProductCard, Storefront } from './models';

@Injectable({ providedIn: 'root' })
export class ApiService {
  constructor(private http: HttpClient) {}

  marketProducts(params: Record<string, string | number> = {}) {
    return this.http.get<Paginated<ProductCard[]>>('/api/market/products', { params });
  }

  marketProduct(slug: string) {
    return this.http.get<{ data: ProductCard }>(`/api/market/products/${slug}`);
  }

  marketStores() {
    return this.http.get<Paginated<Storefront[]>>('/api/market/stores');
  }

  marketStore(slug: string) {
    return this.http.get<{ data: { store: Storefront; products: ProductCard[] } }>(`/api/market/stores/${slug}`);
  }

  marketCategories() {
    return this.http.get<{ data: Category[] }>('/api/market/categories');
  }

  cart() {
    return this.http.get<{ data: CartPayload }>('/api/cart');
  }

  addToCart(variantId: number, qty: number) {
    return this.http.post<{ data: CartPayload }>('/api/cart/items', { variant_id: variantId, qty });
  }

  updateCartItem(id: number, qty: number) {
    return this.http.patch<{ data: CartPayload }>(`/api/cart/items/${id}`, { qty });
  }

  removeCartItem(id: number) {
    return this.http.delete<{ data: CartPayload }>(`/api/cart/items/${id}`);
  }

  addresses() {
    return this.http.get<{ data: Address[] }>('/api/addresses');
  }

  createAddress(payload: Partial<Address>) {
    return this.http.post<{ data: Address }>('/api/addresses', payload);
  }

  checkoutQuote() {
    return this.http.post<{ data: CartPayload }>('/api/checkout/quote', {});
  }

  checkout(shippingAddressId: number, idempotencyKey: string) {
    return this.http.post<any>('/api/checkout', { shipping_address_id: shippingAddressId }, {
      headers: { 'Idempotency-Key': idempotencyKey },
    });
  }

  mockPay(url: string) {
    return this.http.get<any>(url);
  }

  myOrders() {
    return this.http.get<Paginated<any[]>>('/api/orders');
  }

  myOrder(id: number) {
    return this.http.get<{ data: any }>(`/api/orders/${id}`);
  }

  cancelOrder(id: number) {
    return this.http.post<{ data: any }>(`/api/orders/${id}/cancel`, {});
  }

  sellerDashboard() {
    return this.http.get<{ data: any }>('/api/tenant/dashboard/summary');
  }

  sellerProducts(params: Record<string, string | number> = {}) {
    return this.http.get<Paginated<any[]>>('/api/tenant/products', { params });
  }

  createProduct(payload: any) {
    return this.http.post<{ data: any }>('/api/tenant/products', payload);
  }

  updateProduct(id: number, payload: any) {
    return this.http.patch<{ data: any }>(`/api/tenant/products/${id}`, payload);
  }

  sellerOrders(params: Record<string, string | number> = {}) {
    return this.http.get<Paginated<any[]>>('/api/tenant/orders', { params });
  }

  updateSellerOrderStatus(id: number, status: string) {
    return this.http.patch<{ data: any }>(`/api/tenant/orders/${id}/status`, { status });
  }

  sellerStores() {
    return this.http.get<Paginated<any[]>>('/api/tenant/stores');
  }

  sellerCategories() {
    return this.http.get<{ data: Category[] }>('/api/tenant/categories');
  }

  createCategory(payload: { name: string }) {
    return this.http.post<{ data: Category }>('/api/tenant/categories', payload);
  }

  lowStock() {
    return this.http.get<{ data: any[] }>('/api/tenant/inventory/low-stock');
  }

  adminMetrics() {
    return this.http.get<{ data: any }>('/api/admin/metrics');
  }

  adminTenants(params: Record<string, string | number> = {}) {
    return this.http.get<Paginated<any[]>>('/api/admin/tenants', { params });
  }

  updateTenantStatus(id: number, status: string) {
    return this.http.patch<{ data: any }>(`/api/admin/tenants/${id}`, { status });
  }

  adminOrders() {
    return this.http.get<Paginated<any[]>>('/api/admin/orders');
  }

  adminAuditLogs() {
    return this.http.get<Paginated<any[]>>('/api/admin/audit-logs');
  }

  registerTenant(payload: any) {
    return this.http.post<{ data: any }>('/api/tenants/register', payload);
  }

  clickAd(impressionId: number) {
    return this.http.post<{ data: { ok: boolean } }>(`/api/market/ads/click/${impressionId}`, {});
  }

  sellerDomains() {
    return this.http.get<{ data: any[] }>('/api/tenant/domains');
  }

  addDomain(domain: string) {
    return this.http.post<{ data: any }>('/api/tenant/domains', { domain });
  }

  verifyDomain(id: number, force = false) {
    return this.http.post<{ data: any }>(`/api/tenant/domains/${id}/verify`, { force });
  }

  sellerAds() {
    return this.http.get<{ data: { balance: string; campaigns: any[] } }>('/api/tenant/ads');
  }

  createAd(payload: any) {
    return this.http.post<{ data: any }>('/api/tenant/ads', payload);
  }

  updateAd(id: number, payload: any) {
    return this.http.patch<{ data: any }>(`/api/tenant/ads/${id}`, payload);
  }

  fundAds(amount: number) {
    return this.http.post<{ data: any }>('/api/tenant/ads/fund', { amount });
  }

  sellerApiKeys() {
    return this.http.get<{ data: any[] }>('/api/tenant/api-keys');
  }

  createApiKey(payload: any) {
    return this.http.post<{ data: { key: any; secret: string } }>('/api/tenant/api-keys', payload);
  }

  revokeApiKey(id: number) {
    return this.http.delete<{ data: any }>(`/api/tenant/api-keys/${id}`);
  }

  sellerWebhooks() {
    return this.http.get<{ data: any[] }>('/api/tenant/webhooks');
  }

  createWebhook(payload: any) {
    return this.http.post<{ data: any }>('/api/tenant/webhooks', payload);
  }

  webhookCatalog() {
    return this.http.get<{ data: string[] }>('/api/tenant/webhooks/catalog');
  }

  aiDescribe(productId: number) {
    return this.http.post<{ data: any }>('/api/tenant/ai/describe', { product_id: productId });
  }

  aiCategorize(productId: number) {
    return this.http.post<{ data: any }>('/api/tenant/ai/categorize', { product_id: productId });
  }

  aiGenerations() {
    return this.http.get<{ data: any[] }>('/api/tenant/ai/generations');
  }

  reviewGeneration(id: number, status: string) {
    return this.http.post<{ data: any }>(`/api/tenant/ai/generations/${id}/review`, { status });
  }

  aiInsights() {
    return this.http.get<{ data: any }>('/api/tenant/ai/insights');
  }

  sellerAnalytics() {
    return this.http.get<{ data: any }>('/api/tenant/analytics');
  }

  adminAnalytics() {
    return this.http.get<{ data: any }>('/api/admin/analytics');
  }

  adminInsights() {
    return this.http.get<{ data: any }>('/api/admin/insights');
  }

  adminAds() {
    return this.http.get<{ data: any[] }>('/api/admin/ads');
  }

  adminDomains() {
    return this.http.get<{ data: any[] }>('/api/admin/domains');
  }

  adminVerifyDomain(id: number) {
    return this.http.post<{ data: any }>(`/api/admin/domains/${id}/verify`, {});
  }

  adminWebhookHealth() {
    return this.http.get<{ data: any }>('/api/admin/webhooks/health');
  }

  adminAiCosts() {
    return this.http.get<{ data: any[] }>('/api/admin/ai-costs');
  }
}
