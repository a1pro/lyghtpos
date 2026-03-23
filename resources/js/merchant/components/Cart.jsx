import React, { Component } from "react";
import { createRoot } from "react-dom/client";
import axios from "axios";
import Swal from "sweetalert2";
import { sum } from "lodash";

class MerchantCart extends Component {
    constructor(props) {
        super(props);
        this.state = {
            cart: [],
            products: [],
            customers: [],
            barcode: "",
            search: "",
            customer_id: "",
            translations: {},
            loading: {
                products: false,
                cart: false,
                updateQty: false
            },
            addingToCart: null, // Track which product is being added
            updatingQty: null    // Track which product's quantity is being updated
        };

        this.loadCart = this.loadCart.bind(this);
        this.handleOnChangeBarcode = this.handleOnChangeBarcode.bind(this);
        this.handleScanBarcode = this.handleScanBarcode.bind(this);
        this.handleChangeQty = this.handleChangeQty.bind(this);
        this.handleEmptyCart = this.handleEmptyCart.bind(this);
        this.loadProducts = this.loadProducts.bind(this);
        this.handleChangeSearch = this.handleChangeSearch.bind(this);
        this.handleSeach = this.handleSeach.bind(this);
        this.setCustomerId = this.setCustomerId.bind(this);
        this.handleClickSubmit = this.handleClickSubmit.bind(this);
        this.loadTranslations = this.loadTranslations.bind(this);
        this.addProductToCart = this.addProductToCart.bind(this);
    }

    componentDidMount() {
        this.loadTranslations();
        this.loadCustomers();
        this.loadProducts();
        this.loadCart();
    }

    loadTranslations() {
        axios.get("/locale/cart")
            .then((res) => {
                this.setState({ translations: res.data });
            })
            .catch((error) => {
                console.error("Error loading translations:", error);
                this.setState({ translations: {} });
            });
    }

    loadCustomers() {
        axios.get(`/merchant/customers`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            this.setState({ customers: res.data });
        }).catch((error) => {
            console.error("Error loading customers:", error);
            this.setState({ customers: [] });
        });
    }

    loadProducts(search = "") {
        this.setState(prev => ({ loading: { ...prev.loading, products: true } }));
        const query = !!search ? `?search=${search}` : "";

        axios.get(`/merchant/products${query}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            const products = res.data.data || [];
            this.setState({ products, loading: { ...this.state.loading, products: false } });
        }).catch((error) => {
            console.error("Error loading products:", error);
            this.setState({ products: [], loading: { ...this.state.loading, products: false } });
        });
    }

    handleOnChangeBarcode(event) {
        this.setState({ barcode: event.target.value });
    }

    loadCart() {
        this.setState(prev => ({ loading: { ...prev.loading, cart: true } }));

        axios.get("/merchant/cart", {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            console.log("cart dataaa", res);
            const cart = Array.isArray(res.data) ? res.data : [];
            this.setState({ cart, loading: { ...this.state.loading, cart: false } });
        }).catch((error) => {
            console.error("Error loading cart:", error);
            this.setState({ cart: [], loading: { ...this.state.loading, cart: false } });
        });
    }

    handleScanBarcode(event) {
        event.preventDefault();
        const { barcode, products } = this.state;

        if (!barcode) return;

        // Find product by barcode
        const product = products.find(p => p.barcode === barcode);

        if (product) {
            this.addProductToCart(product.id);
            this.setState({ barcode: "" });
        } else {
            // Try to add by barcode if product not found in current list
            this.setState({ addingToCart: 'barcode' });

            axios.post("/merchant/cart", { barcode })
                .then((res) => {
                    this.loadCart();
                    this.setState({ barcode: "", addingToCart: null });
                    Swal.fire("Success!", "Product added to cart", "success");
                })
                .catch((err) => {
                    this.setState({ addingToCart: null });
                    Swal.fire("Error!", err.response?.data?.message || "Product not found", "error");
                });
        }
    }

    handleChangeQty(product_id, qty) {
        if (qty < 1) return;

        console.log('Updating quantity for product:', {
            product_id: product_id,
            product_id_type: typeof product_id,
            qty: qty
        });

        this.setState({ updatingQty: product_id });

        // Optimistically update UI
        const updatedCart = this.state.cart.map((c) => {
            if (c.id === product_id) {
                return { ...c, pivot: { ...c.pivot, quantity: parseInt(qty) } };
            }
            return c;
        });
        this.setState({ cart: updatedCart });

        axios.post("/merchant/cart/change-qty", { product_id, quantity: qty })
            .then((res) => {
                this.setState({ updatingQty: null });
            })
            .catch((err) => {
                // Revert on error
                this.loadCart();
                this.setState({ updatingQty: null });
                Swal.fire("Error!", err.response?.data?.message || "Failed to update quantity", "error");
            });
    }

    getTotal(cart) {
        if (!cart.length) return "0.00";
        const total = cart.map((c) => (c.pivot?.quantity || 0) * (c.price || 0));
        return sum(total).toFixed(2);
    }

    handleClickDelete(product_id) {
        axios.post("/merchant/cart/delete", { product_id, _method: "DELETE" })
            .then((res) => {
                const cart = this.state.cart.filter((c) => c.id !== product_id);
                this.setState({ cart });
            })
            .catch((err) => {
                Swal.fire("Error!", "Failed to remove product", "error");
            });
    }

    handleEmptyCart() {
        Swal.fire({
            title: "Empty Cart?",
            text: "Are you sure you want to remove all items?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, empty it!",
            cancelButtonText: "No, keep it"
        }).then((result) => {
            if (result.isConfirmed) {
                this.setState({ updatingQty: 'empty' });
                axios.post("/merchant/cart/empty", { _method: "DELETE" })
                    .then((res) => {
                        this.setState({ cart: [], updatingQty: null });
                        Swal.fire("Emptied!", "Your cart has been emptied.", "success");
                    })
                    .catch((err) => {
                        this.setState({ updatingQty: null });
                        Swal.fire("Error!", "Failed to empty cart", "error");
                    });
            }
        });
    }

    handleChangeSearch(event) {
        this.setState({ search: event.target.value });
    }

    handleSeach(event) {
        if (event.keyCode === 13) {
            this.loadProducts(event.target.value);
        }
    }

    addProductToCart(productId) {
        const { products, cart } = this.state;
        const product = products.find(p => p.id === productId);

        if (!product) {
            Swal.fire("Error!", "Product not found", "error");
            return;
        }

        // Set loading state for this specific product
        this.setState({ addingToCart: productId });

        // Check if product is already in cart
        const cartItem = cart.find(c => c.id === product.id);

        if (cartItem) {
            // Check stock before incrementing
            if (product.quantity > cartItem.pivot.quantity) {
                const newQuantity = cartItem.pivot.quantity + 1;

                // Update quantity
                this.setState({ updatingQty: product.id });

                axios.post("/merchant/cart/change-qty", { product_id: product.id, quantity: newQuantity })
                    .then((res) => {
                        // Reload cart to get latest data
                        this.loadCart();
                        this.setState({ addingToCart: null, updatingQty: null });
                        Swal.fire("Added!", `${product.name} quantity increased`, "success");
                    })
                    .catch((err) => {
                        this.setState({ addingToCart: null, updatingQty: null });
                        Swal.fire("Error!", err.response?.data?.message || "Failed to update quantity", "error");
                    });
            } else {
                this.setState({ addingToCart: null });
                Swal.fire("Out of Stock!", "No more items available", "warning");
            }
        } else {
            if (product.quantity > 0) {
                // Add to cart via API
                axios.post("/merchant/cart", { product_id: product.id })
                    .then((res) => {
                        this.loadCart(); // Reload cart to get latest
                        this.setState({ addingToCart: null });
                        Swal.fire("Added!", `${product.name} added to cart`, "success");
                    })
                    .catch((err) => {
                        this.setState({ addingToCart: null });
                        Swal.fire("Error!", err.response?.data?.message || "Failed to add product", "error");
                    });
            } else {
                this.setState({ addingToCart: null });
                Swal.fire("Out of Stock!", `${product.name} is out of stock`, "warning");
            }
        }
    }

    setCustomerId(event) {
        this.setState({ customer_id: event.target.value });
    }

    handleClickSubmit() {
        window.location.href = "/merchant/cart/checkout";
        return;

        if (!this.state.cart.length) {
            Swal.fire("Error!", "Your cart is empty", "warning");
            return;
        }

        Swal.fire({
            title: this.state.translations["received_amount"] || "Received Amount",
            input: "text",
            inputValue: this.getTotal(this.state.cart),
            cancelButtonText: this.state.translations["cancel_pay"] || "Cancel",
            showCancelButton: true,
            confirmButtonText: this.state.translations["confirm_pay"] || "Confirm",
            showLoaderOnConfirm: true,
            preConfirm: (amount) => {
                return axios
                    .post("/merchant/orders", {
                        customer_id: this.state.customer_id,
                        amount,
                    })
                    .then((res) => {
                        this.loadCart();
                        return res.data;
                    })
                    .catch((err) => {
                        Swal.showValidationMessage(err.response?.data?.message || "Failed to process order");
                    });
            },
            allowOutsideClick: () => !Swal.isLoading(),
        }).then((result) => {
            if (result.value) {
                Swal.fire("Success!", "Order completed successfully!", "success");
            }
        });
    }

    render() {
        const { cart = [], products = [], customers = [], barcode = "", translations = {}, loading, addingToCart, updatingQty } = this.state;

        return (
            <div className="row">
                <div className="col-md-6 col-lg-4">
                    <div className="row mb-2">
                        <div className="col">
                            <form onSubmit={this.handleScanBarcode}>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder={translations["scan_barcode"] || "Scan Barcode"}
                                    value={barcode}
                                    onChange={this.handleOnChangeBarcode}
                                    disabled={addingToCart !== null || updatingQty !== null}
                                />
                            </form>
                        </div>
                        <div className="col">
                            <select
                                className="form-control"
                                onChange={this.setCustomerId}
                                disabled={addingToCart !== null || updatingQty !== null}
                            >
                                <option value="">
                                    {translations["general_customer"] || "General Customer"}
                                </option>
                                {customers.map((cus) => (
                                    <option
                                        key={cus.id}
                                        value={cus.id}
                                    >{`${cus.first_name} ${cus.last_name}`}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="user-cart">
                        <div className="card">
                            <table className="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{translations["product_name"] || "Product"}</th>
                                        <th>{translations["quantity"] || "Qty"}</th>
                                        <th className="text-right">
                                            {translations["price"] || "Price"}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {loading.cart ? (
                                        <tr>
                                            <td colSpan="3" className="text-center">
                                                <div className="spinner-border spinner-border-sm" role="status">
                                                </div>
                                                <span className="ms-2">Loading cart...</span>
                                            </td>
                                        </tr>
                                    ) : cart.length === 0 ? (
                                        <tr>
                                            <td colSpan="3" className="text-center">Cart is empty</td>
                                        </tr>
                                    ) : (
                                        cart.map((c) => (
                                            <tr key={c.id}>
                                                <td>{c.name}</td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        className="form-control form-control-sm qty"
                                                        value={c.pivot?.quantity || 0}
                                                        onChange={(event) =>
                                                            this.handleChangeQty(
                                                                c.id,
                                                                parseInt(event.target.value) || 0
                                                            )
                                                        }
                                                        disabled={updatingQty === c.id || addingToCart !== null}
                                                        min="1"
                                                        style={{ width: '80px' }}
                                                    />
                                                    <button
                                                        className="btn btn-danger btn-sm mt-1"
                                                        onClick={() =>
                                                            this.handleClickDelete(c.id)
                                                        }
                                                        disabled={updatingQty === c.id || addingToCart !== null}
                                                    >
                                                        <i className="fas fa-trash"></i>
                                                    </button>
                                                    {updatingQty === c.id && (
                                                        <span className="ms-2">
                                                            <small className="text-muted">Updating...</small>
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    {window.APP.currency_symbol}{" "}
                                                    {((c.price || 0) * (c.pivot?.quantity || 0)).toFixed(2)}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="row mt-3">
                        <div className="col"><strong>{translations["total"] || "Total"}:</strong></div>
                        <div className="col text-right">
                            <strong>{window.APP.currency_symbol} {this.getTotal(cart)}</strong>
                        </div>
                    </div>
                    <div className="row mt-2">
                        <div className="col">
                            <button
                                type="button"
                                className="btn btn-danger btn-block"
                                onClick={this.handleEmptyCart}
                                disabled={!cart.length || updatingQty !== null || addingToCart !== null}
                            >
                                {updatingQty === 'empty' ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Emptying...
                                    </>
                                ) : (
                                    translations["cancel"] || "Cancel"
                                )}
                            </button>
                        </div>
                        <div className="col">
                            <button
                                type="button"
                                className="btn btn-primary btn-block"
                                disabled={!cart.length || updatingQty !== null || addingToCart !== null}
                                onClick={this.handleClickSubmit}
                            >
                                {translations["checkout"] || "Checkout"}
                            </button>
                        </div>
                    </div>
                </div>
                <div className="col-md-6 col-lg-8">
                    {/* Search Input */}
                    <div className="search-container mb-4">
                        <div className="position-relative">
                            <input
                                type="text"
                                className="form-control form-control-lg ps-5"
                                placeholder={(translations["search_product"] || "Search products") + "..."}
                                onChange={this.handleChangeSearch}
                                onKeyDown={this.handleSeach}
                                disabled={loading.products || addingToCart !== null || updatingQty !== null}
                                style={{
                                    borderRadius: '12px',
                                    border: '1px solid #e2e8f0',
                                    padding: '12px 16px 12px 45px',
                                    fontSize: '0.95rem',
                                    transition: 'all 0.2s ease'
                                }}
                                onFocus={(e) => e.target.style.borderColor = '#3b82f6'}
                                onBlur={(e) => e.target.style.borderColor = '#e2e8f0'}
                            />
                        </div>
                    </div>

                    {/* Products Grid */}
                    <div className="products-grid">
                        {loading.products ? (
                            <div className="loading-state">
                                <div className="spinner-border text-primary" role="status" style={{ width: '40px', height: '40px' }}>
                                </div>
                                <p className="text-muted mt-3 mb-0">Loading products...</p>
                            </div>
                        ) : products.length === 0 ? (
                            <div className="empty-state">
                                <i className="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p className="text-muted mb-0">No products found</p>
                                <small className="text-muted">Try searching with different keywords</small>
                            </div>
                        ) : (
                            products.map((p) => (
                                <div
                                    onClick={() => addingToCart === null && updatingQty === null && this.addProductToCart(p.id)}
                                    key={p.id}
                                    className="product-card"
                                    style={{
                                        cursor: addingToCart === null && updatingQty === null ? 'pointer' : 'not-allowed',
                                        opacity: addingToCart !== null || updatingQty !== null ? 0.6 : 1,
                                        transition: 'all 0.2s ease'
                                    }}
                                    onMouseEnter={(e) => {
                                        if (addingToCart === null && updatingQty === null) {
                                            e.currentTarget.style.transform = 'translateY(-2px)';
                                            e.currentTarget.style.boxShadow = '0 8px 16px -8px rgba(0, 0, 0, 0.1)';
                                        }
                                    }}
                                    onMouseLeave={(e) => {
                                        e.currentTarget.style.transform = 'translateY(0)';
                                        e.currentTarget.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.08)';
                                    }}
                                >
                                    {/* Product Image */}
                                    <div className="product-image-wrapper">
                                        <img
                                            src={p.image_url || `https://placehold.co/300x300?text=No+Image`}
                                            alt={p.name}
                                            className="product-image"
                                        />
                                        {addingToCart === p.id && (
                                            <div className="product-overlay">
                                                <div className="spinner-border spinner-border-sm text-white" role="status">
                                                </div>
                                            </div>
                                        )}
                                        {p.quantity <= 0 && (
                                            <div className="out-of-stock-badge">
                                                Out of Stock
                                            </div>
                                        )}
                                    </div>

                                    {/* Product Info */}
                                    <div className="product-info">
                                        <h5 className="product-title" title={p.name}>
                                            {p.name}
                                        </h5>

                                        <div className="product-details">
                                            <div className="product-price-wrapper">
                                                <span className="product-price">
                                                    {window.APP.currency_symbol} {parseFloat(p.price || 0).toFixed(2)}
                                                </span>
                                                {p.quantity > 0 && p.quantity < 10 && (
                                                    <span className="stock-badge low-stock">
                                                        {p.quantity} left
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    <style jsx>{`
                        .search-container {
                            position: sticky;
                            top: 0;
                            background: white;
                            z-index: 10;
                            padding: 8px 0;
                        }

                        .products-grid {
                            display: grid;
                            grid-template-columns: repeat(4, 1fr);
                            gap: 16px;
                            padding: 8px 0 24px 0;
                        }

                        .product-card {
                            background: white;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
                            transition: all 0.2s ease;
                            cursor: pointer;
                        }

                        .product-image-wrapper {
                            position: relative;
                            width: 100%;
                            padding-top: 100%;
                            background: #f8f9fa;
                            overflow: hidden;
                        }

                        .product-image {
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                            transition: transform 0.3s ease;
                        }

                        .product-card:hover .product-image {
                            transform: scale(1.05);
                        }

                        .product-overlay {
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.5);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            backdrop-filter: blur(2px);
                        }

                        .out-of-stock-badge {
                            position: absolute;
                            top: 8px;
                            right: 8px;
                            background: #ef4444;
                            color: white;
                            padding: 4px 8px;
                            border-radius: 6px;
                            font-size: 0.7rem;
                            font-weight: 600;
                            z-index: 1;
                        }

                        .product-info {
                            padding: 12px;
                        }

                        .product-title {
                            font-size: 0.85rem;
                            font-weight: 600;
                            color: #1f2937;
                            margin: 0 0 8px 0;
                            line-height: 1.4;
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                            min-height: 38px;
                        }

                        .product-details {
                            display: flex;
                            flex-direction: column;
                            gap: 6px;
                        }

                        .product-price-wrapper {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            flex-wrap: wrap;
                            gap: 6px;
                        }

                        .product-price {
                            font-size: 1rem;
                            font-weight: 700;
                            color: #3b82f6;
                        }

                        .stock-badge {
                            font-size: 0.65rem;
                            padding: 2px 6px;
                            border-radius: 10px;
                            background: #fef3c7;
                            color: #d97706;
                            font-weight: 500;
                        }

                        .low-stock {
                            background: #fee2e2;
                            color: #dc2626;
                        }

                        .loading-state, .empty-state {
                            grid-column: 1 / -1;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            padding: 60px 20px;
                            text-align: center;
                        }

                        .empty-state i {
                            color: #d1d5db;
                        }

                        /* Tablet: 3 items per row */
                        @media (max-width: 1200px) and (min-width: 769px) {
                            .products-grid {
                                grid-template-columns: repeat(3, 1fr);
                                gap: 14px;
                            }
                        }

                        /* Mobile: 2 items per row */
                        @media (max-width: 768px) {
                            .products-grid {
                                grid-template-columns: repeat(2, 1fr);
                                gap: 12px;
                            }
                            
                            .product-info {
                                padding: 10px;
                            }
                            
                            .product-title {
                                font-size: 0.8rem;
                                min-height: 36px;
                            }
                            
                            .product-price {
                                font-size: 0.9rem;
                            }
                        }

                        /* Small mobile: 2 items per row with smaller padding */
                        @media (max-width: 480px) {
                            .products-grid {
                                gap: 10px;
                            }
                            
                            .product-info {
                                padding: 8px;
                            }
                            
                            .product-title {
                                font-size: 0.75rem;
                                min-height: 34px;
                                margin-bottom: 6px;
                            }
                            
                            .product-price {
                                font-size: 0.85rem;
                            }
                            
                            .stock-badge {
                                font-size: 0.6rem;
                                padding: 2px 4px;
                            }
                        }
                    `}</style>
                </div>
            </div>
        );
    }
}

export default MerchantCart;

const root = document.getElementById("merchant_cart");
if (root) {
    const rootInstance = createRoot(root);
    rootInstance.render(<MerchantCart />);
}