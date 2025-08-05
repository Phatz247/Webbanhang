 
      function renderCart() {
        const cartData = JSON.parse(localStorage.getItem("cart")) || [];
        const cartContent = document.getElementById("cart-content");

        if (cartData.length === 0) {
          cartContent.innerHTML = `<p class="empty">Bạn chưa mua sản phẩm nào.</p>`;
          return;
        }

        let total = 0;

        let table = `
          <table>
            <thead>
              <tr>
                <th>Ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Size</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
        `;

        cartData.forEach((item, index) => {
          const itemTotal = item.price * item.quantity;
          total += itemTotal;

          table += `
            <tr>
              <td><img src="${item.image}" alt="Ảnh sản phẩm" /></td>
              <td>${item.name}</td>
              <td>${item.size || "-"}</td>
              <td>${item.price.toLocaleString()} đ</td>
              <td>${item.quantity}</td>
              <td>${itemTotal.toLocaleString()} đ</td>
              <td>
                <button class="btn-remove" onclick="removeItem(${index})">Xóa</button>
              </td>
            </tr>
          `;
        });

        table += `
            </tbody>
          </table>
          <p class="total">Tổng cộng: ${total.toLocaleString()} đ</p>
        `;

        cartContent.innerHTML = table;
      }

      function removeItem(index) {
        let cartData = JSON.parse(localStorage.getItem("cart")) || [];
        cartData.splice(index, 1);
        localStorage.setItem("cart", JSON.stringify(cartData));
        renderCart();
      }

      renderCart();
