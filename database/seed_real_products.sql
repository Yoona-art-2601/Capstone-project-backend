START TRANSACTION;

UPDATE products SET name = 'Seaweed Chicken Breast', category = 'Beacon Chicken', price = 10.00, sale_price = NULL, sale_start = NULL, sale_end = NULL, image_url = 'uploads/products/seed_seaweed-chicken-breast.webp', description = 'Boneless Beacon seaweed chicken breast. Lean and high in protein, great for grilling, stir-fry or salads.', is_active = 1 WHERE product_id = 1;
UPDATE products SET name = 'Premium Chile Atlantic Salmon Portion Cut', category = 'Seafood', price = 26.00, sale_price = NULL, sale_start = NULL, sale_end = NULL, image_url = 'uploads/products/seed_premium-chile-atlantic-salmon-portion-cut.webp', description = 'Imported Chilean Atlantic salmon, air flown and frozen in portion cuts. Rich in omega-3, perfect for pan-searing or baking.', is_active = 1 WHERE product_id = 3;
UPDATE products SET name = 'Organic Rolled Oats (500g) | Radiant', category = 'Dried Goods', price = 10.50, sale_price = NULL, sale_start = NULL, sale_end = NULL, image_url = 'uploads/products/seed_rolled-oats.webp', description = 'Certified organic whole rolled oats from Radiant. A wholesome breakfast for porridge, overnight oats or baking.', is_active = 1 WHERE product_id = 7;

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Chicken Whole Leg (2 pieces)', 'Beacon Chicken', 14.50, NULL, 'uploads/products/seed_seaweed-chicken-whole-leg.webp', 'Two whole legs from Beacon seaweed chicken. Juicy and tender, ideal for roasting, curry or soup.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Chicken Whole Leg (2 pieces)');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 30 WHEN 2 THEN 24 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Chicken Whole Leg (2 pieces)' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Chicken Whole Leg (2 pieces)' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Chicken Fillet (10 pieces)', 'Beacon Chicken', 16.00, NULL, 'uploads/products/seed_beacon-seaweed-fillet.webp', 'Ten boneless chicken fillets from Beacon seaweed chicken. Quick to cook and easy to portion.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Chicken Fillet (10 pieces)');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 26 WHEN 2 THEN 20 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Chicken Fillet (10 pieces)' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Chicken Fillet (10 pieces)' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Chicken Mix Part (750g)', 'Beacon Chicken', 15.00, 13.80, 'uploads/products/seed_beacon-seaweed-chicken-mix-part.webp', 'A mix of Beacon seaweed chicken parts, about 750g per pack. Good value for family meals and soups.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Chicken Mix Part (750g)');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 22 WHEN 2 THEN 18 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Chicken Mix Part (750g)' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Chicken Mix Part (750g)' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Whole Chicken', 'Beacon Chicken', 30.00, NULL, 'uploads/products/seed_beacon-seaweed-natural-chicken.webp', 'Whole Beacon seaweed chicken, cleaned and frozen. Raised on a natural seaweed diet with no antibiotics.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Whole Chicken');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 15 WHEN 2 THEN 12 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Whole Chicken' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Whole Chicken' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Chicken Feet (10 pieces)', 'Beacon Chicken', 4.00, NULL, 'uploads/products/seed_seaweed-chicken-feet.webp', 'Ten cleaned chicken feet from Beacon seaweed chicken. Rich in collagen, great for soup or braising.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Chicken Feet (10 pieces)');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 40 WHEN 2 THEN 35 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Chicken Feet (10 pieces)' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Chicken Feet (10 pieces)' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Chicken Eggs (15 pieces)', 'Beacon Chicken', 15.70, NULL, 'uploads/products/seed_beacon-seaweed-chicken-eggs.webp', 'Fifteen fresh eggs from Beacon seaweed-fed hens. Rich yolk and great taste for everyday cooking.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Chicken Eggs (15 pieces)');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 36 WHEN 2 THEN 30 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Chicken Eggs (15 pieces)' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Chicken Eggs (15 pieces)' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Kampung Seaweed Whole Chicken', 'Beacon Chicken', 48.00, NULL, 'uploads/products/seed_seaweed-kampung-chicken.webp', 'Whole kampung chicken raised on a seaweed diet. Firmer texture and full flavour, best for herbal soup.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Kampung Seaweed Whole Chicken');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 10 WHEN 2 THEN 8 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Kampung Seaweed Whole Chicken' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Kampung Seaweed Whole Chicken' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Marinated Seaweed Chicken Drumettes (5pcs) | Ready to Cook', 'Ready to Cook', 9.90, NULL, 'uploads/products/seed_beacon-seaweed-marinated-chicken-drumettes.webp', 'Five seaweed chicken drumettes pre-marinated and frozen. Air fry or bake for a quick meal.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Marinated Seaweed Chicken Drumettes (5pcs) | Ready to Cook');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 28 WHEN 2 THEN 24 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Marinated Seaweed Chicken Drumettes (5pcs) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Marinated Seaweed Chicken Drumettes (5pcs) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Marinated Seaweed Chicken Fillet (220g) | Ready to Cook', 'Ready to Cook', 9.90, NULL, 'uploads/products/seed_beacon-seaweed-marinated-chicken-fillet.webp', 'Marinated seaweed chicken fillet, 220g. Pan-fry for a few minutes and serve with rice or salad.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Marinated Seaweed Chicken Fillet (220g) | Ready to Cook');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 25 WHEN 2 THEN 20 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Marinated Seaweed Chicken Fillet (220g) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Marinated Seaweed Chicken Fillet (220g) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Marinated Seaweed Chicken Chop (220g) | Ready to Cook', 'Ready to Cook', 11.90, NULL, 'uploads/products/seed_beacon-seaweed-marinated-chicken-chop.webp', 'Boneless marinated seaweed chicken chop, 220g. A quick Western-style meal at home.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Marinated Seaweed Chicken Chop (220g) | Ready to Cook');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 24 WHEN 2 THEN 20 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Marinated Seaweed Chicken Chop (220g) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Marinated Seaweed Chicken Chop (220g) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Chicken Wantan (15 pieces) | Ready to Cook', 'Ready to Cook', 10.90, NULL, 'uploads/products/seed_rtc-wantan-15pcs.webp', 'Fifteen chicken wantan made with Beacon seaweed chicken. Boil for soup or deep fry as a snack.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Chicken Wantan (15 pieces) | Ready to Cook');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 30 WHEN 2 THEN 26 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Chicken Wantan (15 pieces) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Chicken Wantan (15 pieces) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Seaweed Chicken Soup (300ml) | Ready to Heat', 'Ready to Cook', 5.00, NULL, 'uploads/products/seed_rtc-seaweed-chicken-soup.webp', 'Ready to heat chicken soup brewed from Beacon seaweed chicken, 300ml. Just warm up and enjoy.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Seaweed Chicken Soup (300ml) | Ready to Heat');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 40 WHEN 2 THEN 32 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Seaweed Chicken Soup (300ml) | Ready to Heat' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Seaweed Chicken Soup (300ml) | Ready to Heat' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Kampung Seaweed Rendang Mixed Chicken (400g) | Ready to Cook', 'Ready to Cook', 22.80, NULL, 'uploads/products/seed_rtc-kampung-rendang-mixed-chicken.webp', 'Kampung seaweed chicken in rendang spices, 400g. Cook until tender for an easy Malaysian favourite.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Kampung Seaweed Rendang Mixed Chicken (400g) | Ready to Cook');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 14 WHEN 2 THEN 10 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Kampung Seaweed Rendang Mixed Chicken (400g) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Kampung Seaweed Rendang Mixed Chicken (400g) | Ready to Cook' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Sabah Deep Sea Flower Prawn 41/48 (750g) | Wild Caught and Frozen', 'Seafood', 55.90, NULL, 'uploads/products/seed_deep-sea-sabah-flower-prawn-41-48-750g.webp', 'Wild caught deep sea flower prawns from Sabah, size 41/48 per kg, 750g. Sweet and firm.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Sabah Deep Sea Flower Prawn 41/48 (750g) | Wild Caught and Frozen');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 12 WHEN 2 THEN 10 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Sabah Deep Sea Flower Prawn 41/48 (750g) | Wild Caught and Frozen' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Sabah Deep Sea Flower Prawn 41/48 (750g) | Wild Caught and Frozen' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Premium Chile Coho Salmon Steak Cut | Imported Air Flown and Frozen', 'Seafood', 17.90, NULL, 'uploads/products/seed_premium-chile-coho-salmon-steak-cut.webp', 'Chilean coho salmon cut into steaks, air flown and frozen. Mild flavour, good for grilling.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Premium Chile Coho Salmon Steak Cut | Imported Air Flown and Frozen');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 18 WHEN 2 THEN 14 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Premium Chile Coho Salmon Steak Cut | Imported Air Flown and Frozen' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Premium Chile Coho Salmon Steak Cut | Imported Air Flown and Frozen' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Norway Saba Mackerel Fillet (2-3 pieces) | Imported Air Flown and Frozen', 'Seafood', 34.60, NULL, 'uploads/products/seed_norway-saba-mackerel-fillet.webp', 'Norwegian saba mackerel fillets, 2 to 3 pieces. Oily and flavourful, perfect grilled with salt.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Norway Saba Mackerel Fillet (2-3 pieces) | Imported Air Flown and Frozen');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 15 WHEN 2 THEN 12 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Norway Saba Mackerel Fillet (2-3 pieces) | Imported Air Flown and Frozen' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Norway Saba Mackerel Fillet (2-3 pieces) | Imported Air Flown and Frozen' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Canadian Scallops (500g)', 'Seafood', 69.90, NULL, 'uploads/products/seed_canadian-scallops.webp', 'Frozen Canadian scallops, 500g. Sweet and tender, great for pan-searing or pasta.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Canadian Scallops (500g)');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 8 WHEN 2 THEN 6 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Canadian Scallops (500g)' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Canadian Scallops (500g)' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Norway Cod Fish Ball (250g)', 'Seafood', 10.90, NULL, 'uploads/products/seed_cod-fish-ball.webp', 'Fish balls made with Norwegian cod, 250g. Bouncy texture for soups, noodles or steamboat.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Norway Cod Fish Ball (250g)');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 30 WHEN 2 THEN 25 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Norway Cod Fish Ball (250g)' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Norway Cod Fish Ball (250g)' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Sabah Red Snapper (Ikan Merah) Steak Cut | Wild Caught and Frozen', 'Seafood', 16.00, NULL, 'uploads/products/seed_wild-caught-sabah-red-snapper-steak-cut.webp', 'Wild caught Sabah red snapper cut into steaks. Firm white flesh for curry, steaming or frying.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Sabah Red Snapper (Ikan Merah) Steak Cut | Wild Caught and Frozen');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 16 WHEN 2 THEN 12 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Sabah Red Snapper (Ikan Merah) Steak Cut | Wild Caught and Frozen' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Sabah Red Snapper (Ikan Merah) Steak Cut | Wild Caught and Frozen' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Sabah Grouper (Kerapu) Fillet | Wild Caught and Frozen', 'Seafood', 24.00, NULL, 'uploads/products/seed_wild-caught-sabah-grouper-fillet.webp', 'Boneless wild caught Sabah grouper fillet. Smooth and delicate, ideal for steaming or fish soup.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Sabah Grouper (Kerapu) Fillet | Wild Caught and Frozen');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 14 WHEN 2 THEN 10 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Sabah Grouper (Kerapu) Fillet | Wild Caught and Frozen' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Sabah Grouper (Kerapu) Fillet | Wild Caught and Frozen' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Organic Rata Honey (500g) | Radiant', 'Health & Wellness', 85.00, NULL, 'uploads/products/seed_organic-rata-honey-500g.webp', 'Organic New Zealand rata honey, 500g. Creamy texture with a mild floral taste.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Organic Rata Honey (500g) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 8 WHEN 2 THEN 6 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Organic Rata Honey (500g) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Organic Rata Honey (500g) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Manuka Honey Drops (MGO 400+) | Radiant', 'Health & Wellness', 6.90, NULL, 'uploads/products/seed_radiant-manuka-honey-drops-mgo400.webp', 'Soothing honey drops made with MGO 400+ Manuka honey. Handy to carry for a sore throat.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Manuka Honey Drops (MGO 400+) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 40 WHEN 2 THEN 30 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Manuka Honey Drops (MGO 400+) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Manuka Honey Drops (MGO 400+) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Pure Peppermint Essential Oil (5ml) | Renosme', 'Health & Wellness', 49.00, NULL, 'uploads/products/seed_pure-peppermint-essential-oil-5ml--renosme.webp', 'Pure peppermint essential oil, 5ml. A cooling scent to refresh and relieve stuffiness.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Pure Peppermint Essential Oil (5ml) | Renosme');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 12 WHEN 2 THEN 10 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Pure Peppermint Essential Oil (5ml) | Renosme' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Pure Peppermint Essential Oil (5ml) | Renosme' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Qi Tonic American Ginseng Herbal Soup (75g) | BaoKang TCM', 'Health & Wellness', 15.00, NULL, 'uploads/products/seed_american-ginseng-qi-tonic-herbal-soup.webp', 'Herbal soup pack with American ginseng, 75g. Cook with chicken for a nourishing family soup.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Qi Tonic American Ginseng Herbal Soup (75g) | BaoKang TCM');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 20 WHEN 2 THEN 16 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Qi Tonic American Ginseng Herbal Soup (75g) | BaoKang TCM' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Qi Tonic American Ginseng Herbal Soup (75g) | BaoKang TCM' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Organic Pure Pomegranate Juice (946ml) | ReGen', 'Beverages', 69.00, NULL, 'uploads/products/seed_regen-organic-pure-pomegranate-nfc-juice.webp', 'Organic pure pomegranate juice, not from concentrate and with no added sugar, 946ml.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Organic Pure Pomegranate Juice (946ml) | ReGen');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 10 WHEN 2 THEN 8 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Organic Pure Pomegranate Juice (946ml) | ReGen' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Organic Pure Pomegranate Juice (946ml) | ReGen' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Lakewood Pure Blueberry Juice (946ml) | Radiant', 'Beverages', 88.00, NULL, 'uploads/products/seed_organic-pure-blueberry-juice.webp', 'Lakewood pure blueberry juice, not from concentrate, 946ml. Naturally rich in antioxidants.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Lakewood Pure Blueberry Juice (946ml) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 8 WHEN 2 THEN 6 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Lakewood Pure Blueberry Juice (946ml) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Lakewood Pure Blueberry Juice (946ml) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Lakewood Pure Apple Juice (946ml) | Radiant', 'Beverages', 32.80, NULL, 'uploads/products/seed_lakewood-apple-juice.webp', 'Lakewood pure apple juice, pressed from fresh apples with nothing added, 946ml.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Lakewood Pure Apple Juice (946ml) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 14 WHEN 2 THEN 12 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Lakewood Pure Apple Juice (946ml) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Lakewood Pure Apple Juice (946ml) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Honey Apple Cider Vinegar Sparkling Drink | Radiant', 'Beverages', 3.80, NULL, 'uploads/products/seed_radiant-honey-apple-cider-sparkling-250ml.webp', 'Sparkling drink with apple cider vinegar and honey, 250ml can. Light and refreshing.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Honey Apple Cider Vinegar Sparkling Drink | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 48 WHEN 2 THEN 40 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Honey Apple Cider Vinegar Sparkling Drink | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Honey Apple Cider Vinegar Sparkling Drink | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Organic Trio Quinoa (500g) | Radiant', 'Dried Goods', 31.90, NULL, 'uploads/products/seed_trio-quinoa.webp', 'A mix of white, red and black organic quinoa, 500g. Gluten free and high in protein.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Organic Trio Quinoa (500g) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 14 WHEN 2 THEN 12 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Organic Trio Quinoa (500g) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Organic Trio Quinoa (500g) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Natural Raw Almond (200g) | Radiant', 'Dried Goods', 25.90, NULL, 'uploads/products/seed_almond-natural.webp', 'Natural raw almonds, 200g. A healthy snack or topping for oats and salads.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Natural Raw Almond (200g) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 20 WHEN 2 THEN 16 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Natural Raw Almond (200g) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Natural Raw Almond (200g) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Natural Raw Walnuts (200g) | Radiant', 'Dried Goods', 22.90, NULL, 'uploads/products/seed_walnut-natural.webp', 'Natural raw walnuts, 200g. Rich in healthy fats, great for snacking or baking.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Natural Raw Walnuts (200g) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 18 WHEN 2 THEN 15 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Natural Raw Walnuts (200g) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Natural Raw Walnuts (200g) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Organic Chia Seeds (200g) | Radiant', 'Dried Goods', 18.50, NULL, 'uploads/products/seed_chia-seeds.webp', 'Organic chia seeds, 200g. Add to drinks, yoghurt or oats for extra fibre.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Organic Chia Seeds (200g) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 22 WHEN 2 THEN 18 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Organic Chia Seeds (200g) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Organic Chia Seeds (200g) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Organic Apple Cider Vinegar (750ml) | Radiant', 'Pantry', 29.90, NULL, 'uploads/products/seed_apple-cider-vinegar-750ml.webp', 'Organic apple cider vinegar, 750ml. Use in salad dressing or mix with water and honey.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Organic Apple Cider Vinegar (750ml) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 15 WHEN 2 THEN 12 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Organic Apple Cider Vinegar (750ml) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Organic Apple Cider Vinegar (750ml) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Gluten Free Low Carb Healthy Noodle (210g) | Radiant', 'Pantry', 13.90, NULL, 'uploads/products/seed_radiant-gluten-free-low-carb-noodle-healthy-noodle.webp', 'Gluten free, low carb noodles, 210g. A lighter choice for soup or dry noodles.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Gluten Free Low Carb Healthy Noodle (210g) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 24 WHEN 2 THEN 20 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Gluten Free Low Carb Healthy Noodle (210g) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Gluten Free Low Carb Healthy Noodle (210g) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

INSERT INTO products (name, category, price, sale_price, image_url, description)
SELECT 'Nutritional Yeast Mini Flakes (100g) | Radiant', 'Pantry', 21.90, NULL, 'uploads/products/seed_nutritional-yeast-mini-flakes.webp', 'Nutritional yeast flakes, 100g. Adds a cheesy, savoury taste to pasta, popcorn and soups.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Nutritional Yeast Mini Flakes (100g) | Radiant');
INSERT INTO branch_stock (branch_id, product_id, stock, low_stock_threshold)
SELECT b.branch_id, p.product_id, CASE b.branch_id WHEN 1 THEN 16 WHEN 2 THEN 12 ELSE 0 END, 10
FROM products p CROSS JOIN branches b
WHERE p.name = 'Nutritional Yeast Mini Flakes (100g) | Radiant' AND NOT EXISTS (SELECT 1 FROM branch_stock bs WHERE bs.branch_id = b.branch_id AND bs.product_id = p.product_id);
INSERT INTO warehouse_stock (warehouse_id, product_id, stock)
SELECT w.warehouse_id, p.product_id, 0
FROM products p CROSS JOIN warehouses w
WHERE p.name = 'Nutritional Yeast Mini Flakes (100g) | Radiant' AND NOT EXISTS (SELECT 1 FROM warehouse_stock ws WHERE ws.warehouse_id = w.warehouse_id AND ws.product_id = p.product_id);

COMMIT;
