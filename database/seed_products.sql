CREATE TEMPORARY TABLE seed_products (
  name varchar(150),
  category varchar(50),
  price decimal(10,2),
  stock_klang int,
  stock_shah_alam int
);

INSERT INTO seed_products VALUES
('Seaweed Chicken Drumettes (10 pcs)', 'Beacon Chicken', 10.00, 45, 38),
('Seaweed Chicken Wingettes (10 pcs)', 'Beacon Chicken', 10.00, 40, 4),
('Marinated Seaweed Chicken Wingettes (5 pcs)', 'Ready to Cook', 9.90, 30, 25),
('Marinated Seaweed Chicken Breast (270g)', 'Ready to Cook', 11.90, 28, 22),
('Seaweed Chicken Nuggets', 'Ready to Cook', 11.80, 35, 30),
('Chicken Cocktail Sausages (250g)', 'Ready to Cook', 13.90, 3, 18),
('Sabah Deep Sea Flower Prawn 31/38 (750g)', 'Seafood', 59.90, 12, 10),
('Sabah Deep Sea Tiger Prawn 9/12 (4 pcs)', 'Seafood', 62.20, 8, 0),
('Wild Caught Sabah Pink Prawn Meat (250g)', 'Seafood', 21.90, 20, 15),
('Wild Caught Sabah Indian Mackerel (Ikan Kembung)', 'Seafood', 14.80, 25, 20),
('Sabah Fish Belly (150g)', 'Seafood', 5.00, 40, 35),
('Organic Manuka Honey MGO100+ (500g)', 'Health & Wellness', 98.00, 10, 6),
('Seaweed Chicken Pure Essence (80ml x 6 packs)', 'Health & Wellness', 118.00, 15, 12),
('Angkor Bird Nest (70ml x 6 bottles)', 'Health & Wellness', 348.00, 5, 3),
('PappaRich 2 in 1 White Coffee', 'Beverages', 18.30, 30, 26),
('PappaRich Stevia White Coffee', 'Beverages', 19.90, 24, 20),
('Organic Pure Cranberry Juice (946ml)', 'Beverages', 69.00, 10, 8),
('Lakewood Pure Tart Cherry Juice (946ml)', 'Beverages', 88.00, 8, 2),
('Organic Shoyu (Radiant)', 'Pantry', 13.90, 18, 15);

INSERT INTO products (name, category, price)
SELECT s.name, s.category, s.price
FROM seed_products s
WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.name = s.name);

INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT 1, p.product_id, s.stock_klang, 10
FROM seed_products s JOIN products p ON p.name = s.name
WHERE NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = 1 AND bs.product_id = p.product_id);

INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT 2, p.product_id, s.stock_shah_alam, 10
FROM seed_products s JOIN products p ON p.name = s.name
WHERE NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = 2 AND bs.product_id = p.product_id);

DROP TEMPORARY TABLE seed_products;
