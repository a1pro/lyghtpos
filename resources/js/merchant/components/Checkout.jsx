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
            customerId: "",
            selectedPayment: "cash",
            loading: false,
            loadingCart: false,
            translations: {},
        };

        this.loadCart = this.loadCart.bind(this);
        this.handlePaymentSelect = this.handlePaymentSelect.bind(this);
        this.loadTranslations = this.loadTranslations.bind(this);
        this.processCashPayment = this.processCashPayment.bind(this);
        this.processUnytiPayment = this.processUnytiPayment.bind(this);
        this.processGenericPayment = this.processGenericPayment.bind(this);
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
        const urlParams = new URLSearchParams(window.location.search);
        const encryptedCustomerId = urlParams.get('cid');

        if (!encryptedCustomerId) {
            Swal.fire({
                title: "URL Malformed",
                text: "Customer not found or url was malformed please try again",
                icon: "warning",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = "/merchant/cart";
            });
            return;
        }

        this.setState({ loadingCart: true });
        axios.get("/merchant/checkout/data", {
            params: {
                cid: encryptedCustomerId
            },
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then((res) => {
            if (res.data.success) {
                const { cart, total, customerid } = res.data;
                if (!customerid || customerid === '') {
                    Swal.fire({
                        title: "URL Malformed",
                        text: "Customer not found or url was malformed please try again",
                        icon: "warning",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "/merchant/cart";
                    });
                    return;
                }

                this.setState({
                    cart: cart,
                    amountDue: total,
                    customerId: customerid
                });
            }
        }).catch((error) => {
            console.error("Error loading cart:", error);
            this.setState({ cart: [], amountDue: 0, customerId: "" });
        }).finally(() => {
            this.setState({ loadingCart: false });
        });
    }

    handlePaymentSelect(method) {
        this.setState({ selectedPayment: method });
    }

    processCashPayment(receivedAmount) {
        this.setState({ loading: true });

        const { amountDue, customerId } = this.state;

        if (!customerId || customerId === '') {
            Swal.fire({
                title: "URL Malformed",
                text: "Customer not found or url was malformed please try again",
                icon: "warning",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = "/merchant/cart";
            });
            return;
        }

        axios.post("/merchant/checkout/process", {
            payment_method: "cash",
            amount: amountDue,
            customer_id: customerId,
            received_amount: receivedAmount
        }).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: "Payment Successful!",
                    html: `
                        <div class="text-center">
                            <p><strong>Order #:</strong> ${res.data.order_number || res.data.order_id}</p>
                            <p><strong>Amount Paid:</strong> ${window.APP.currency_symbol} ${receivedAmount}</p>
                            <p><strong>Status:</strong> Completed</p>
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

        const { amountDue, customerId } = this.state;

        if (!customerId || customerId === '') {
            Swal.fire({
                title: "URL Malformed",
                text: "Customer not found or url was malformed please try again",
                icon: "warning",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = "/merchant/cart";
            });
            return;
        }

        axios.post("/merchant/checkout/process", {
            payment_method: "unyti",
            amount: amountDue,
            customer_id: customerId,
            unyti_user_id: unytiUserId
        }).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: "Unyti Payment Initiated!",
                    html: `
                        <div class="text-center">
                            <p>Your order has been created.</p>
                            <p><strong>Order #:</strong> ${res.data.order_number || res.data.order_id}</p>
                            <p><strong>Status:</strong> ${res.data.status || 'Pending'}</p>
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

    processGenericPayment(method, successTitle, successMessage) {
        this.setState({ loading: true });

        const { amountDue, customerId } = this.state;

        if (!customerId || customerId === '') {
            Swal.fire({
                title: "URL Malformed",
                text: "Customer not found or url was malformed please try again",
                icon: "warning",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = "/merchant/cart";
            });
            return;
        }

        axios.post("/merchant/checkout/process", {
            payment_method: method,
            amount: amountDue,
            customer_id: customerId
        }).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: successTitle,
                    html: `
                        <div class="text-center">
                            <p><strong>Order #:</strong> ${res.data.order_number || res.data.order_id}</p>
                            <p>${successMessage}</p>
                            <p><strong>Status:</strong> ${res.data.status || 'Pending'}</p>
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
            Swal.fire("Error", error.response?.data?.message || "Failed to process order", "error");
        }).finally(() => {
            this.setState({ loading: false });
        });
    }

    handleCashPayment() {
        const { amountDue } = this.state;

        Swal.fire({
            title: "Cash Payment",
            html: `
                <div class="text-center">
                    <p><strong>Amount Due:</strong> ${window.APP.currency_symbol} ${amountDue}</p>
                    <p>Confirm payment to complete the order.</p>
                </div>
            `,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Complete Payment",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                this.processCashPayment(amountDue);
            }
        });
    }

    handleUnytiPayment() {
        const { amountDue, customerId } = this.state;

        if (!customerId || customerId === '') {
            Swal.fire({
                title: "URL Malformed",
                text: "Customer not found or url was malformed please try again",
                icon: "warning",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = "/merchant/cart";
            });
            return;
        }

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

    handlePaxPayment() {
        this.processGenericPayment(
            'pax',
            'Order Sent to PAX',
            'The order has been sent to the PAX device for processing.'
        );
    }

    handlePosMobilePayment() {
        this.processGenericPayment(
            'pos_mobile',
            'Order Sent to POS Mobile App',
            'The order has been sent to the POS mobile app for approval.'
        );
    }

    render() {
        const { cart = [], amountDue, selectedPayment, loading, loadingCart } = this.state;

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
                                    {/* Pay with Cash */}
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
                                                Pay with Cash
                                            </label>
                                            <div className="text-muted small mt-1">Complete order immediately</div>
                                        </div>
                                    </div>

                                    {/* Send to PAX */}
                                    <div
                                        className={`payment-option border rounded mb-3 p-3 ${selectedPayment === 'pax' ? 'border-primary bg-light' : 'border'}`}
                                        onClick={() => this.handlePaymentSelect('pax')}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                checked={selectedPayment === 'pax'}
                                                onChange={() => this.handlePaymentSelect('pax')}
                                                id="payPax"
                                            />
                                            <label className="form-check-label fw-bold" htmlFor="payPax">
                                                Send to PAX
                                            </label>
                                            <div className="text-muted small mt-1">Send order to PAX device for processing</div>
                                        </div>
                                    </div>

                                    {/* Send to POS Mobile App */}
                                    <div
                                        className={`payment-option border rounded mb-3 p-3 ${selectedPayment === 'pos_mobile' ? 'border-primary bg-light' : 'border'}`}
                                        onClick={() => this.handlePaymentSelect('pos_mobile')}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                checked={selectedPayment === 'pos_mobile'}
                                                onChange={() => this.handlePaymentSelect('pos_mobile')}
                                                id="payPosMobile"
                                            />
                                            <label className="form-check-label fw-bold" htmlFor="payPosMobile">
                                                Send to POS Mobile App
                                            </label>
                                            <div className="text-muted small mt-1">Send order to POS mobile app for approval</div>
                                        </div>
                                    </div>

                                    {/* Use Unyti Credits */}
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
                                                Use Unyti Credits
                                            </label>
                                            <div className="text-muted small mt-1">Pay with Unyti credits</div>
                                        </div>
                                    </div>
                                </div>

                                {/* Proceed button for all methods */}
                                <button
                                    className="btn btn-primary btn-lg w-100 mt-4"
                                    onClick={() => {
                                        if (selectedPayment === 'cash') {
                                            this.handleCashPayment();
                                        } else if (selectedPayment === 'unyti') {
                                            this.handleUnytiPayment();
                                        } else if (selectedPayment === 'pax') {
                                            this.handlePaxPayment();
                                        } else if (selectedPayment === 'pos_mobile') {
                                            this.handlePosMobilePayment();
                                        }
                                    }}
                                    disabled={loading || loadingCart}
                                >
                                    {loading ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2"></span>
                                            Processing...
                                        </>
                                    ) : (
                                        "Proceed to Pay"
                                    )}
                                </button>
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
                                <div className="d-flex justify-content-between mt-3 pt-2 border-top">
                                    <strong className="fs-5">Amount Due</strong>
                                    <strong className="fs-5 text-primary">
                                        {window.APP.currency_symbol} {amountDue.toFixed(2)}
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