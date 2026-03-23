import React, { Component } from "react";
import { createRoot } from "react-dom/client";
import axios from "axios";
import Swal from "sweetalert2";
import { QRCodeCanvas } from "qrcode.react";

class MerchantCheckout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            cart: [],
            amountDue: 0,
            selectedPayment: "cash",
            loading: false,
            loadingCart: false,
            translations: {},
            // Card payment form state
            cardData: {
                card_number: "",
                expiry_month: "",
                expiry_year: "",
                cvv: "",
                billing: {
                    first_name: "",
                    last_name: "",
                    address: "",
                    city: "",
                    state: "",
                    zip: "",
                    country: ""
                }
            },
            formErrors: {}
        };

        this.loadCart = this.loadCart.bind(this);
        this.handlePaymentSelect = this.handlePaymentSelect.bind(this);
        this.handleCardPaymentSubmit = this.handleCardPaymentSubmit.bind(this);
        this.loadTranslations = this.loadTranslations.bind(this);
        this.handleCardInputChange = this.handleCardInputChange.bind(this);
        this.handleBillingInputChange = this.handleBillingInputChange.bind(this);
        this.processCardPayment = this.processCardPayment.bind(this);
        this.processCashPayment = this.processCashPayment.bind(this);
        this.processUnytiPayment = this.processUnytiPayment.bind(this);
        this.validateCardForm = this.validateCardForm.bind(this);
        this.formatCardNumber = this.formatCardNumber.bind(this);
    }

    componentDidMount() {
        this.loadTranslations();
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

    loadCart() {
        this.setState({ loadingCart: true });
        axios.get("/merchant/checkout/data", {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            if (res.data.success) {
                const { cart, total } = res.data;
                this.setState({ cart, amountDue: total });
            }
        }).catch((error) => {
            console.error("Error loading cart:", error);
            this.setState({ cart: [], amountDue: 0 });
        }).finally(() => {
            this.setState({ loadingCart: false });
        });
    }

    calculateTotal(cart) {
        if (!cart.length) return 0;
        const total = cart.reduce((sum, item) => {
            return sum + ((item.price || 0) * (item.pivot?.quantity || 0));
        }, 0);
        return parseFloat(total.toFixed(2));
    }

    handlePaymentSelect(method) {
        this.setState({
            selectedPayment: method,
            formErrors: {}
        });
    }

    handleCardInputChange(e) {
        const { name, value } = e.target;

        // Format card number on the fly
        if (name === 'card_number') {
            const formattedValue = this.formatCardNumber(value);
            this.setState(prev => ({
                cardData: {
                    ...prev.cardData,
                    [name]: formattedValue
                },
                formErrors: {
                    ...prev.formErrors,
                    [name]: ''
                }
            }));
        } else {
            this.setState(prev => ({
                cardData: {
                    ...prev.cardData,
                    [name]: value
                },
                formErrors: {
                    ...prev.formErrors,
                    [name]: ''
                }
            }));
        }
    }

    handleBillingInputChange(e) {
        const { name, value } = e.target;
        this.setState(prev => ({
            cardData: {
                ...prev.cardData,
                billing: {
                    ...prev.cardData.billing,
                    [name]: value
                }
            },
            formErrors: {
                ...prev.formErrors,
                [name]: ''
            }
        }));
    }

    formatCardNumber(value) {
        const cleaned = value.replace(/\D/g, '');
        const limited = cleaned.slice(0, 16);

        const groups = limited.match(/.{1,4}/g);
        return groups ? groups.join(' ') : limited;
    }

    validateCardForm() {
        const { cardData } = this.state;
        const errors = {};

        const cardNumberClean = cardData.card_number.replace(/\s/g, '');
        if (!cardNumberClean) {
            errors.card_number = "Card number is required";
        } else if (!cardNumberClean.match(/^\d{16}$/)) {
            errors.card_number = "Please enter a valid 16-digit card number";
        }

        if (!cardData.expiry_month) {
            errors.expiry_month = "Expiry month is required";
        }
        if (!cardData.expiry_year) {
            errors.expiry_year = "Expiry year is required";
        }

        if (cardData.expiry_month && cardData.expiry_year) {
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth() + 1;
            const expYear = parseInt(cardData.expiry_year);
            const expMonth = parseInt(cardData.expiry_month);

            if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
                errors.expiry = "Card has expired";
            }
        }

        if (!cardData.cvv) {
            errors.cvv = "CVV is required";
        } else if (!cardData.cvv.match(/^\d{3,4}$/)) {
            errors.cvv = "Please enter a valid CVV (3-4 digits)";
        }

        if (!cardData.billing.first_name) {
            errors.first_name = "First name is required";
        }
        if (!cardData.billing.last_name) {
            errors.last_name = "Last name is required";
        }
        if (!cardData.billing.address) {
            errors.address = "Address is required";
        }
        if (!cardData.billing.city) {
            errors.city = "City is required";
        }
        if (!cardData.billing.state) {
            errors.state = "State is required";
        }
        if (!cardData.billing.zip) {
            errors.zip = "ZIP code is required";
        }
        if (!cardData.billing.country) {
            errors.country = "Country is required";
        }

        this.setState({ formErrors: errors });
        return Object.keys(errors).length === 0;
    }

    handleCardPaymentSubmit(e) {
        e.preventDefault();

        if (!this.validateCardForm()) {
            Swal.fire("Error", "Please fill in all required fields correctly", "error");
            return;
        }

        // Show confirmation dialog before processing
        const { amountDue, cardData } = this.state;
        const cardAmount = (amountDue * 1.035).toFixed(2);

        Swal.fire({
            title: "Confirm Card Payment",
            html: `
                <div class="text-left">
                    <p><strong>Amount to Pay:</strong> ${window.APP.currency_symbol} ${cardAmount}</p>
                    <p><strong>Card:</strong> **** **** **** ${cardData.card_number.slice(-4)}</p>
                    <p><strong>Card Fee (3.5%):</strong> ${window.APP.currency_symbol} ${(amountDue * 0.035).toFixed(2)}</p>
                    <p><strong>Billing Name:</strong> ${cardData.billing.first_name} ${cardData.billing.last_name}</p>
                </div>
                <div class="mt-3 text-warning">
                    <i class="fas fa-exclamation-triangle"></i> This action cannot be undone
                </div>
            `,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, Process Payment",
            cancelButtonText: "Cancel",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33"
        }).then((result) => {
            if (result.isConfirmed) {
                this.processCardPayment();
            }
        });
    }

    processCardPayment() {
        this.setState({ loading: true });

        const { amountDue, cardData } = this.state;

        // Prepare card data for API
        const cardPayload = {
            card_number: cardData.card_number.replace(/\s/g, ''),
            expiry_month: cardData.expiry_month,
            expiry_year: cardData.expiry_year,
            cvv: cardData.cvv,
            billing: cardData.billing,
            order_description: `Order from merchant POS - ${new Date().toLocaleString()}`
        };

        axios.post("/merchant/checkout/process", {
            payment_method: "card",
            amount: amountDue,
            card_data: cardPayload
        }).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: "Payment Successful!",
                    html: `
                        <div class="text-center">
                            <p><strong>Order ID:</strong> ${res.data.order_id}</p>
                            <p><strong>Transaction ID:</strong> ${res.data.transaction_id}</p>
                            <p><strong>Amount Paid:</strong> ${window.APP.currency_symbol} ${(amountDue * 1.035).toFixed(2)}</p>
                            <p><strong>Card:</strong> **** **** **** ${cardData.card_number.slice(-4)}</p>
                        </div>
                    `,
                    icon: "success",
                    confirmButtonText: "Done"
                }).then(() => {
                    window.location.href = "/merchant/orders";
                });
            } else {
                Swal.fire("Payment Failed", res.data.message, "error");
            }
        }).catch((error) => {
            console.error("Payment error:", error);
            Swal.fire("Error", error.response?.data?.message || "Payment processing failed", "error");
        }).finally(() => {
            this.setState({ loading: false });
        });
    }

    processCashPayment(receivedAmount) {
        this.setState({ loading: true });

        const { amountDue } = this.state;

        axios.post("/merchant/checkout/process", {
            payment_method: "cash",
            amount: amountDue,
            received_amount: receivedAmount
        }).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: "Payment Successful!",
                    html: `
                        <div class="text-center">
                            <p><strong>Order ID:</strong> ${res.data.order_id}</p>
                            <p><strong>Amount Paid:</strong> ${window.APP.currency_symbol} ${receivedAmount}</p>
                        </div>
                    `,
                    icon: "success",
                    confirmButtonText: "Done"
                }).then(() => {
                    window.location.href = "/merchant/orders";
                });
            } else {
                Swal.fire("Error", res.data.message, "error");
            }
        }).catch((error) => {
            Swal.fire("Error", error.response?.data?.message || "Failed to process order", "error");
        }).finally(() => {
            this.setState({ loading: false });
        });
    }

    processUnytiPayment(unytiUserId) {
        this.setState({ loading: true });

        const { amountDue } = this.state;

        axios.post("/merchant/checkout/process", {
            payment_method: "unyti",
            amount: amountDue,
            unyti_user_id: unytiUserId
        }).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: "Unyti Payment Initiated!",
                    html: `
                        <div class="text-center">
                            <p>Your order has been created.</p>
                            <p><strong>Order ID:</strong> ${res.data.order_id}</p>
                            <p>Please complete the payment in your Unyti app.</p>
                        </div>
                    `,
                    icon: "info",
                    confirmButtonText: "Done"
                }).then(() => {
                    window.location.href = "/merchant/orders";
                });
            } else {
                Swal.fire("Error", res.data.message, "error");
            }
        }).catch((error) => {
            Swal.fire("Error", error.response?.data?.message || "Failed to initiate payment", "error");
        }).finally(() => {
            this.setState({ loading: false });
        });
    }

    handleCashPayment() {
        const { amountDue } = this.state;

        Swal.fire({
            title: "Cash Payment",
            text: `Amount Due: ${window.APP.currency_symbol} ${amountDue}`,
            showCancelButton: true,
            confirmButtonText: "Complete Payment",
            preConfirm: () => {
                if (amountDue < 0) {
                    Swal.showValidationMessage("Insufficient amount received");
                    return false;
                }
                if (isNaN(parseFloat(receivedAmount))) {
                    Swal.showValidationMessage("Please enter a valid amount");
                    return false;
                }
                return { receivedAmount: parseFloat(amountDue).toFixed(2) };
            }
        }).then((result) => {
            if (result.value) {
                this.processCashPayment(result.value.receivedAmount);
            }
        });
    }

    handleUnytiPayment() {
        const { amountDue } = this.state;

        Swal.fire({
            title: "Unyti Payment",
            html: `
                <div class="text-center">
                    <p><strong>Amount to Pay:</strong> ${window.APP.currency_symbol} ${amountDue}</p>
                    <p>Scan QR code with Unyti app to pay</p>
                    <div class="d-flex justify-content-center mt-3">
                        <div class="p-3 border rounded bg-white">
                            <QRCodeCanvas 
                                value="unyti://pay?amount=${amountDue}&merchant=${window.APP.merchant_id || 'demo'}&currency=${window.APP.currency_code || 'USD'}" 
                                size={200}
                                level="H"
                                includeMargin={true}
                            />
                        </div>
                    </div>
                    <p class="mt-3 text-muted small">Or enter Unyti ID:</p>
                    <input type="text" class="form-control" placeholder="Unyti User ID" id="unytiUserId" />
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "Confirm Payment",
            preConfirm: () => {
                const unytiUserId = document.getElementById('unytiUserId')?.value;
                if (!unytiUserId) {
                    Swal.showValidationMessage("Please enter Unyti User ID");
                    return false;
                }
                return { unytiUserId };
            }
        }).then((result) => {
            if (result.value) {
                this.processUnytiPayment(result.value.unytiUserId);
            }
        });
    }

    renderCardForm() {
        const { cardData, formErrors } = this.state;

        return (
            <div className="card-payment-form mt-4 p-3 border rounded bg-light">
                <h5 className="mb-3">Card Payment Details</h5>
                <form onSubmit={this.handleCardPaymentSubmit}>
                    <div className="mb-3">
                        <label className="form-label fw-bold">Card Number *</label>
                        <input
                            type="text"
                            className={`form-control ${formErrors.card_number ? 'is-invalid' : ''}`}
                            name="card_number"
                            placeholder="1234 5678 9012 3456"
                            value={cardData.card_number}
                            onChange={this.handleCardInputChange}
                            maxLength="19"
                            disabled={this.state.loading}
                        />
                        {formErrors.card_number && (
                            <div className="invalid-feedback">{formErrors.card_number}</div>
                        )}
                    </div>

                    <div className="row mb-3">
                        <div className="col-6">
                            <label className="form-label fw-bold">Expiry Month *</label>
                            <select
                                className={`form-control ${formErrors.expiry_month ? 'is-invalid' : ''}`}
                                name="expiry_month"
                                value={cardData.expiry_month}
                                onChange={this.handleCardInputChange}
                                disabled={this.state.loading}
                            >
                                <option value="">Month</option>
                                {Array.from({ length: 12 }, (_, i) => (
                                    <option key={i} value={String(i + 1).padStart(2, '0')}>
                                        {String(i + 1).padStart(2, '0')}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-6">
                            <label className="form-label fw-bold">Expiry Year *</label>
                            <select
                                className={`form-control ${formErrors.expiry_year ? 'is-invalid' : ''}`}
                                name="expiry_year"
                                value={cardData.expiry_year}
                                onChange={this.handleCardInputChange}
                                disabled={this.state.loading}
                            >
                                <option value="">Year</option>
                                {Array.from({ length: 10 }, (_, i) => (
                                    <option key={i} value={new Date().getFullYear() + i}>
                                        {new Date().getFullYear() + i}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {formErrors.expiry && (
                            <div className="text-danger small mt-1">{formErrors.expiry}</div>
                        )}
                    </div>

                    <div className="mb-3">
                        <label className="form-label fw-bold">CVV *</label>
                        <input
                            type="text"
                            className={`form-control ${formErrors.cvv ? 'is-invalid' : ''}`}
                            name="cvv"
                            placeholder="123"
                            value={cardData.cvv}
                            onChange={this.handleCardInputChange}
                            maxLength="4"
                            disabled={this.state.loading}
                        />
                        {formErrors.cvv && (
                            <div className="invalid-feedback">{formErrors.cvv}</div>
                        )}
                    </div>

                    <hr className="my-3" />

                    <h6 className="mb-3">Billing Address</h6>

                    <div className="row mb-2">
                        <div className="col-6">
                            <input
                                type="text"
                                className={`form-control ${formErrors.first_name ? 'is-invalid' : ''}`}
                                name="first_name"
                                placeholder="First Name *"
                                value={cardData.billing.first_name}
                                onChange={this.handleBillingInputChange}
                                disabled={this.state.loading}
                            />
                            {formErrors.first_name && (
                                <div className="invalid-feedback">{formErrors.first_name}</div>
                            )}
                        </div>
                        <div className="col-6">
                            <input
                                type="text"
                                className={`form-control ${formErrors.last_name ? 'is-invalid' : ''}`}
                                name="last_name"
                                placeholder="Last Name *"
                                value={cardData.billing.last_name}
                                onChange={this.handleBillingInputChange}
                                disabled={this.state.loading}
                            />
                            {formErrors.last_name && (
                                <div className="invalid-feedback">{formErrors.last_name}</div>
                            )}
                        </div>
                    </div>

                    <div className="mb-2">
                        <input
                            type="text"
                            className={`form-control ${formErrors.address ? 'is-invalid' : ''}`}
                            name="address"
                            placeholder="Address *"
                            value={cardData.billing.address}
                            onChange={this.handleBillingInputChange}
                            disabled={this.state.loading}
                        />
                        {formErrors.address && (
                            <div className="invalid-feedback">{formErrors.address}</div>
                        )}
                    </div>

                    <div className="row mb-2">
                        <div className="col-6">
                            <input
                                type="text"
                                className={`form-control ${formErrors.city ? 'is-invalid' : ''}`}
                                name="city"
                                placeholder="City *"
                                value={cardData.billing.city}
                                onChange={this.handleBillingInputChange}
                                disabled={this.state.loading}
                            />
                            {formErrors.city && (
                                <div className="invalid-feedback">{formErrors.city}</div>
                            )}
                        </div>
                        <div className="col-3">
                            <input
                                type="text"
                                className={`form-control ${formErrors.state ? 'is-invalid' : ''}`}
                                name="state"
                                placeholder="State *"
                                value={cardData.billing.state}
                                onChange={this.handleBillingInputChange}
                                disabled={this.state.loading}
                            />
                            {formErrors.state && (
                                <div className="invalid-feedback">{formErrors.state}</div>
                            )}
                        </div>
                        <div className="col-3">
                            <input
                                type="text"
                                className={`form-control ${formErrors.zip ? 'is-invalid' : ''}`}
                                name="zip"
                                placeholder="ZIP *"
                                value={cardData.billing.zip}
                                onChange={this.handleBillingInputChange}
                                disabled={this.state.loading}
                            />
                            {formErrors.zip && (
                                <div className="invalid-feedback">{formErrors.zip}</div>
                            )}
                        </div>
                    </div>

                    <div className="mb-3">
                        <input
                            type="text"
                            className={`form-control ${formErrors.country ? 'is-invalid' : ''}`}
                            name="country"
                            placeholder="Country *"
                            value={cardData.billing.country}
                            onChange={this.handleBillingInputChange}
                            disabled={this.state.loading}
                        />
                        {formErrors.country && (
                            <div className="invalid-feedback">{formErrors.country}</div>
                        )}
                    </div>

                    <div className="text-muted small mt-2 mb-3">
                        <i className="fas fa-lock me-1"></i> Your payment is secure with Authorize.net
                    </div>

                    <button
                        type="submit"
                        className="btn btn-primary btn-lg w-100"
                        disabled={this.state.loading}
                    >
                        {this.state.loading ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2"></span>
                                Processing...
                            </>
                        ) : (
                            `Pay ${window.APP.currency_symbol} ${(this.state.amountDue * 1.035).toFixed(2)}`
                        )}
                    </button>
                </form>
            </div>
        );
    }

    render() {
        const { cart = [], amountDue, selectedPayment, loading, loadingCart } = this.state;
        const cardAmount = (amountDue * 1.035).toFixed(2);

        return (
            <div className="container py-4">
                <div className="row">
                    <div className="col-md-7">
                        <div className="card shadow-sm">
                            <div className="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h4 className="mb-0">Payment Methods</h4>
                            </div>
                            <div className="card-body">
                                <div className="payment-options">
                                    {/* Pay Cash */}
                                    <div
                                        className={`payment-option border rounded mb-3 p-3 ${selectedPayment === 'cash' ? 'border-primary bg-light' : 'border'}`}
                                        onClick={() => this.handlePaymentSelect('cash')}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                checked={selectedPayment === 'cash'}
                                                onChange={() => this.handlePaymentSelect('cash')}
                                                id="payCash"
                                            />
                                            <label className="form-check-label fw-bold" htmlFor="payCash">
                                                Pay Cash
                                            </label>
                                            <div className="text-muted small mt-1">No Reward</div>
                                        </div>
                                    </div>

                                    {/* Pay Credit/Debit */}
                                    <div
                                        className={`payment-option border rounded mb-3 p-3 ${selectedPayment === 'card' ? 'border-primary bg-light' : 'border'}`}
                                        onClick={() => this.handlePaymentSelect('card')}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                checked={selectedPayment === 'card'}
                                                onChange={() => this.handlePaymentSelect('card')}
                                                id="payCard"
                                            />
                                            <label className="form-check-label fw-bold" htmlFor="payCard">
                                                Pay Credit/Debit
                                            </label>
                                            <div className="text-muted small mt-1">Pay 3.5% more. Secured by Authorize.net</div>
                                        </div>
                                    </div>

                                    {/* Unyti */}
                                    <div
                                        className={`payment-option border rounded mb-3 p-3 ${selectedPayment === 'unyti' ? 'border-primary bg-light' : 'border'}`}
                                        onClick={() => this.handlePaymentSelect('unyti')}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                checked={selectedPayment === 'unyti'}
                                                onChange={() => this.handlePaymentSelect('unyti')}
                                                id="payUnyti"
                                            />
                                            <label className="form-check-label fw-bold" htmlFor="payUnyti">
                                                Unyti
                                            </label>
                                            <div className="text-muted small mt-1">Choose Community (reward chosen by community)</div>
                                        </div>
                                    </div>
                                </div>

                                {/* Show Card Form when card is selected */}
                                {selectedPayment === 'card' && this.renderCardForm()}

                                {/* Show Proceed button for cash and unyti */}
                                {selectedPayment !== 'card' && (
                                    <button
                                        className="btn btn-primary btn-lg w-100 mt-4"
                                        onClick={() => {
                                            if (selectedPayment === 'cash') {
                                                this.handleCashPayment();
                                            } else if (selectedPayment === 'unyti') {
                                                this.handleUnytiPayment();
                                            }
                                        }}
                                        disabled={loading || loadingCart}
                                    >
                                        {loading ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2"></span>
                                            </>
                                        ) : (
                                            "Proceed to Pay"
                                        )}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Right Column - Order Summary */}
                    <div className="col-md-5">
                        <div className="card shadow-sm mb-4">
                            <div className="card-header bg-white border-bottom-0 pt-4">
                                <h4 className="mb-0">Order Summary</h4>
                            </div>
                            <div className="card-body">
                                <div className="order-items mb-3" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                                    {loadingCart ? (
                                        <div className="text-center p-3">
                                            <div className="spinner-border spinner-border-sm" role="status">
                                                <span className="visually-hidden">Loading...</span>
                                            </div>
                                            <span className="ms-2">Loading cart...</span>
                                        </div>
                                    ) : cart.length === 0 ? (
                                        <p className="text-muted text-center p-3">No items in cart</p>
                                    ) : (
                                        cart.map((item, index) => (
                                            <div key={index} className="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                <div>
                                                    <span className="fw-bold">{item.name}</span>
                                                    <span className="text-muted ms-2">x{item.pivot?.quantity}</span>
                                                </div>
                                                <span>
                                                    {window.APP.currency_symbol} {((item.price || 0) * (item.pivot?.quantity || 0)).toFixed(2)}
                                                </span>
                                            </div>
                                        ))
                                    )}
                                </div>
                                <hr />
                                <div className="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span>{window.APP.currency_symbol} {amountDue.toFixed(2)}</span>
                                </div>
                                {selectedPayment === 'card' && (
                                    <div className="d-flex justify-content-between mb-2 text-success">
                                        <span>Card Fee (3.5%)</span>
                                        <span>{window.APP.currency_symbol} {(amountDue * 0.035).toFixed(2)}</span>
                                    </div>
                                )}
                                <div className="d-flex justify-content-between mt-3 pt-2 border-top">
                                    <strong className="fs-5">Amount Due</strong>
                                    <strong className="fs-5 text-primary">
                                        {window.APP.currency_symbol} {selectedPayment === 'card' ? cardAmount : amountDue.toFixed(2)}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        {selectedPayment === 'unyti' && (
                            <div className="card shadow-sm">
                                <div className="card-header bg-white border-bottom-0 pt-4">
                                    <h4 className="mb-0">Scan to Pay</h4>
                                </div>
                                <div className="card-body text-center">
                                    <div className="d-flex justify-content-center mb-3">
                                        <div className="p-3 border rounded bg-white">
                                            <QRCodeCanvas
                                                value={`unyti://pay?amount=${amountDue}&merchant=${window.APP.merchant_id || 'demo'}&currency=${window.APP.currency_code || 'USD'}`}
                                                size={200}
                                                level="H"
                                            />
                                        </div>
                                    </div>
                                    <p className="text-muted small mb-0">
                                        Scan this QR code with Unyti app to pay instantly
                                    </p>
                                    <p className="text-muted small mt-2">
                                        Or click "Proceed to Pay" to enter Unyti ID
                                    </p>
                                </div>
                            </div>
                        )}

                        <div className="mt-3 text-center">
                            <small className="text-muted">
                                <i className="fas fa-lock me-1"></i> Secure payment
                            </small>
                        </div>
                    </div>
                </div>

                <style jsx>{`
                    .payment-option {
                        transition: all 0.2s ease;
                    }
                    .payment-option:hover {
                        background-color: #f8f9fa;
                        transform: translateX(5px);
                    }
                    .payment-option.border-primary {
                        border-width: 2px !important;
                    }
                    .order-items::-webkit-scrollbar {
                        width: 6px;
                    }
                    .order-items::-webkit-scrollbar-track {
                        background: #f1f1f1;
                        border-radius: 3px;
                    }
                    .order-items::-webkit-scrollbar-thumb {
                        background: #888;
                        border-radius: 3px;
                    }
                    .order-items::-webkit-scrollbar-thumb:hover {
                        background: #555;
                    }
                    .card-payment-form {
                        animation: slideDown 0.3s ease;
                    }
                    @keyframes slideDown {
                        from {
                            opacity: 0;
                            transform: translateY(-20px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                `}</style>
            </div>
        );
    }
}

export default MerchantCheckout;

const checkoutRoot = document.getElementById("merchant_checkout");
if (checkoutRoot) {
    const rootInstance = createRoot(checkoutRoot);
    rootInstance.render(<MerchantCheckout />);
}