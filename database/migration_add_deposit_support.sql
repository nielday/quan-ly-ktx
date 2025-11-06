-- Migration: Thêm hỗ trợ tiền đặt cọc vào bảng payments
-- Chạy script này để cập nhật database

-- Bước 1: Thêm cột contract_id (cho phép NULL)
ALTER TABLE payments 
ADD COLUMN contract_id INT NULL COMMENT 'Liên kết với hợp đồng (cho tiền đặt cọc)' AFTER invoice_id,
ADD INDEX idx_contract_id (contract_id);

-- Bước 2: Thêm cột payment_type
ALTER TABLE payments 
ADD COLUMN payment_type ENUM('invoice_payment', 'deposit', 'refund') DEFAULT 'invoice_payment' COMMENT 'Loại thanh toán' AFTER contract_id;

-- Bước 3: Sửa invoice_id để cho phép NULL (cho tiền đặt cọc)
ALTER TABLE payments 
MODIFY COLUMN invoice_id INT NULL COMMENT 'Thanh toán cho hóa đơn nào (NULL nếu là tiền đặt cọc)';

-- Bước 4: Thêm foreign key cho contract_id
ALTER TABLE payments 
ADD CONSTRAINT fk_payments_contract 
FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE;

-- Bước 5: Cập nhật dữ liệu cũ (nếu có) - đánh dấu tất cả payment hiện tại là invoice_payment
UPDATE payments SET payment_type = 'invoice_payment' WHERE payment_type IS NULL;

-- Bước 6: Thêm constraint để đảm bảo logic:
-- - Nếu payment_type = 'invoice_payment' thì invoice_id phải NOT NULL
-- - Nếu payment_type = 'deposit' hoặc 'refund' thì contract_id phải NOT NULL
-- (Lưu ý: MySQL không hỗ trợ CHECK constraint tốt, nên cần validate ở application level)

