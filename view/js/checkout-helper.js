// Checkout restoration helper
function restoreCheckout() {
  const savedState = sessionStorage.getItem("checkout_state");
  if (savedState) {
    const checkoutState = JSON.parse(savedState);

    // Check if state is still valid (within 30 minutes)
    const thirtyMinutes = 30 * 60 * 1000;
    if (Date.now() - checkoutState.timestamp < thirtyMinutes) {
      // Create form to submit checkout data
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "restore_checkout.php";
      form.style.display = "none";

      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "checkout_data";
      input.value = savedState;

      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();

      return true;
    } else {
      // Clear expired state
      sessionStorage.removeItem("checkout_state");
    }
  }
  return false;
}

// Show return to checkout button if there's saved state
document.addEventListener("DOMContentLoaded", function () {
  // Không hiển thị thông báo nếu đang ở trang order_success
  if (window.location.pathname.includes("order_success.php")) {
    sessionStorage.removeItem("checkout_state");
    return;
  }

  const savedState = sessionStorage.getItem("checkout_state");
  if (savedState) {
    const checkoutState = JSON.parse(savedState);
    const thirtyMinutes = 30 * 60 * 1000;

    if (Date.now() - checkoutState.timestamp < thirtyMinutes) {
      // Create return button
      const returnBtn = document.createElement("div");
      returnBtn.className = "return-checkout-banner";
      returnBtn.innerHTML = `
                <div style="background: #e3f2fd; border: 1px solid #2196f3; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
                    <i class="fas fa-shopping-cart" style="color: #2196f3; margin-right: 10px;"></i>
                    <span style="color: #1976d2; font-weight: 600;">Bạn có đơn hàng đang chờ thanh toán</span>
                    <button onclick="restoreCheckout()" style="background: #2196f3; color: white; border: none; padding: 8px 16px; border-radius: 4px; margin-left: 15px; cursor: pointer; font-weight: 500;">
                        <i class="fas fa-arrow-left"></i> Quay lại thanh toán
                    </button>
                </div>
            `;

      // Insert after main content
      const mainContent = document.querySelector(
        ".profile-container, .main-content, body > div:first-child"
      );
      if (mainContent) {
        mainContent.insertBefore(returnBtn, mainContent.firstChild);
      }
    }
  }
});
