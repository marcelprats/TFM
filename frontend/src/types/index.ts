export interface User {
  id: number;
  name: string;
  email: string;
}

export interface Product {
  id: number;
  nom: string;
  descripcio: string;
  preu?: number;
  imatge?: string;
}

export interface CartItem {
  product: Product;
  quantity?: number;
  reserved_price?: number;
}

export interface Cart {
  cart_items: CartItem[];
}
