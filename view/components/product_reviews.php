<?php
// view/components/product_reviews.php
// Require variables from parent scope: $sp (product), $conn (db), $_SESSION['user']
if (!isset($sp)) { return; }
?>
<style>
.reviews-block { margin-top: 30px; background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
.reviews-header { display:flex; align-items:center; justify-content:space-between; margin-bottom: 12px; }
.rating-summary { font-weight:600; color:#222; }
.rating-summary .avg { color:#e67e22; font-size: 18px; }
.reviews-list { margin-top: 12px; display:flex; flex-direction:column; gap:14px; }
.review-item { border-bottom:1px solid #f0f0f0; padding-bottom:12px; }
.review-item:last-child { border-bottom:none; }
.review-meta { color:#777; font-size:13px; margin-bottom:4px; }
.star { color:#f1c40f; }
.review-form { margin-top: 16px; border-top:1px dashed #eee; padding-top:14px; }
.review-form textarea { width:100%; min-height:90px; padding:10px; border:1px solid #ddd; border-radius:8px; resize:vertical; }
.review-form .row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.review-form select, .review-form input[type=number] { padding:8px; border:1px solid #ddd; border-radius:6px; }
.btn-submit-review { background:#27ae60; color:#fff; border:none; border-radius:6px; padding:10px 14px; cursor:pointer; }
.btn-submit-review:disabled { background:#a5d6a7; cursor:not-allowed; }
.alert { padding:10px 12px; border-radius:8px; margin:10px 0; font-size:14px; }
.alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
.alert-error { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
</style>

<div class="reviews-block" id="reviews-block" data-masp="<?= htmlspecialchars($sp['MASP']) ?>">
	<div class="reviews-header">
		<div class="rating-summary">
			<span class="avg" id="avg-rating">0.0</span>/5 · <span id="total-reviews">0</span> đánh giá
		</div>
		<div>
			<a href="#reviews-block">Đánh giá</a>
		</div>
	</div>

	<div id="reviews-messages"></div>

	<?php $user = $_SESSION['user'] ?? null; if ($user && !empty($user['MAKH'])): ?>
	<form id="review-form" class="review-form">
		<input type="hidden" name="action" value="create" />
		<input type="hidden" name="masp" value="<?= htmlspecialchars($sp['MASP']) ?>" />
		<div class="row">
			<label for="review-stars">Số sao:</label>
			<select name="stars" id="review-stars" required>
				<option value="">-- Chọn --</option>
				<?php for ($i=5;$i>=1;$i--): ?>
					<option value="<?= $i ?>"><?= $i ?> sao</option>
				<?php endfor; ?>
			</select>

					<label for="review-mahd">Mã hoá đơn:</label>
					<select name="mahd" id="review-mahd" required>
						<option value="">-- Chọn hoá đơn đủ điều kiện --</option>
					</select>
		</div>
		<div style="margin-top:8px;">
			<label for="review-content">Nội dung (tối đa 500 ký tự):</label>
			<textarea name="content" id="review-content" maxlength="500" placeholder="Chia sẻ cảm nhận của bạn..."></textarea>
		</div>
		<div style="margin-top:10px;">
			<button type="submit" class="btn-submit-review">Gửi đánh giá</button>
		</div>
	</form>
	<?php else: ?>
		<div class="alert alert-error">Vui lòng đăng nhập để đánh giá sản phẩm.</div>
	<?php endif; ?>

	<div class="reviews-list" id="reviews-list"></div>
</div>

<script>
(function(){
	const block = document.getElementById('reviews-block');
	if (!block) return;
	const masp = block.getAttribute('data-masp');
	const listEl = document.getElementById('reviews-list');
	const avgEl = document.getElementById('avg-rating');
	const totalEl = document.getElementById('total-reviews');
	const msgEl = document.getElementById('reviews-messages');

	function showMsg(text, type='success') {
		msgEl.innerHTML = `<div class="alert ${type==='success'?'alert-success':'alert-error'}">${text}</div>`;
		setTimeout(()=>{ msgEl.innerHTML=''; }, 4000);
	}

		function loadReviews() {
		fetch(`/web_3/controller/review_management.php?action=list&masp=${encodeURIComponent(masp)}`)
			.then(r => r.json())
			.then(d => {
				if (!d.ok) { avgEl.textContent='0.0'; totalEl.textContent='0'; listEl.innerHTML=''; return; }
				avgEl.textContent = (d.avg && d.avg.avg) ? d.avg.avg : 0;
				totalEl.textContent = (d.avg && d.avg.total) ? d.avg.total : 0;
				if (!Array.isArray(d.items) || d.items.length===0) { listEl.innerHTML = '<div style="color:#777;">Chưa có đánh giá nào.</div>'; return; }
				listEl.innerHTML = d.items.map(it => `
					<div class="review-item">
						<div class="review-meta">
							<span>${'★'.repeat(it.SOSAODANHGIA)}${'☆'.repeat(5-it.SOSAODANHGIA)}</span>
							· <strong>${it.TENKH ? it.TENKH : 'Khách hàng'}</strong>
							· <span>${it.THOIGIAN}</span>
						</div>
						<div class="review-content">${(it.NOIDUNG||'').replace(/</g,'&lt;')}</div>
					</div>
				`).join('');
			})
			.catch(()=>{});
	}

		loadReviews();

		// Load eligible invoices
		fetch(`/web_3/controller/review_management.php?action=eligible&masp=${encodeURIComponent(masp)}`)
			.then(r=>r.json())
			.then(d=>{
				const sel = document.getElementById('review-mahd');
				if (!sel) return;
				if (!d.ok || !Array.isArray(d.items) || d.items.length===0) {
					sel.innerHTML = '<option value="" disabled>Không tìm thấy hoá đơn đủ điều kiện</option>';
					sel.setAttribute('disabled','disabled');
					return;
				}
				sel.innerHTML = '<option value="">-- Chọn hoá đơn đủ điều kiện --</option>' + d.items.map(it => `<option value="${it.MAHD}">${it.MAHD} (${it.TRANGTHAIGH||''})</option>`).join('');
			})
			.catch(()=>{});

	const form = document.getElementById('review-form');
	if (form) {
		form.addEventListener('submit', function(e){
			e.preventDefault();
			const fd = new FormData(form);
			const stars = parseInt(fd.get('stars')||'0', 10);
			const content = (fd.get('content')||'').trim();
			const mahd = (fd.get('mahd')||'').trim();
			if (!(stars>=1 && stars<=5)) { showMsg('Số sao không hợp lệ', 'error'); return; }
			if (content.length>500) { showMsg('Nội dung tối đa 500 ký tự', 'error'); return; }
			if (!mahd) { showMsg('Vui lòng chọn mã hóa đơn (MAHD) dùng để mua sản phẩm', 'error'); return; }

			fetch('/web_3/controller/review_management.php', {
				method: 'POST',
				body: fd
			})
			.then(r=>r.json())
			.then(d=>{
				if (d.ok) { showMsg(d.message || 'Đã gửi đánh giá'); form.reset(); loadReviews(); }
				else { showMsg(d.message || 'Không thể gửi đánh giá', 'error'); }
			})
			.catch(()=>showMsg('Lỗi kết nối. Vui lòng thử lại.', 'error'));
		});
	}
})();
</script>
