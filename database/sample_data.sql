-- Sample Data for Luxury Watch BI System
USE knowhowpilot_BI;

-- Insert Brands
INSERT INTO brands (name, country, founded_year, luxury_tier, status) VALUES
('Rolex', 'Switzerland', 1905, 'Ultra', 'Active'),
('Patek Philippe', 'Switzerland', 1839, 'Ultra', 'Active'),
('Audemars Piguet', 'Switzerland', 1875, 'Ultra', 'Active'),
('Omega', 'Switzerland', 1848, 'High', 'Active'),
('TAG Heuer', 'Switzerland', 1860, 'High', 'Active'),
('Breitling', 'Switzerland', 1884, 'High', 'Active'),
('Cartier', 'France', 1847, 'Ultra', 'Active'),
('IWC', 'Switzerland', 1868, 'High', 'Active'),
('Jaeger-LeCoultre', 'Switzerland', 1833, 'Ultra', 'Active'),
('Panerai', 'Italy', 1860, 'High', 'Active');

-- Insert Categories
INSERT INTO categories (name, parent_id, description, status) VALUES
('Luxury Watches', NULL, 'High-end timepieces', 'Active'),
('Sports Watches', 1, 'Athletic and diving watches', 'Active'),
('Dress Watches', 1, 'Elegant formal timepieces', 'Active'),
('Complications', 1, 'Watches with complex mechanisms', 'Active'),
('Limited Editions', 1, 'Exclusive limited production watches', 'Active'),
('Vintage Collection', 1, 'Classic and heritage pieces', 'Active');

-- Insert Sample Products
INSERT INTO products (sku, name, brand_id, category_id, model, movement_type, case_material, case_diameter, water_resistance, price, cost, weight, limited_edition, status) VALUES
('RLX-SUB-001', 'Submariner Date', 1, 2, '126610LN', 'Automatic', 'Stainless Steel', 41.0, 300, 285000.00, 142500.00, 155.0, FALSE, 'Active'),
('RLX-GMT-001', 'GMT-Master II', 1, 2, '126710BLNR', 'Automatic', 'Stainless Steel', 40.0, 100, 320000.00, 160000.00, 140.0, FALSE, 'Active'),
('PP-CAL-001', 'Calatrava', 2, 3, '5227G-001', 'Mechanical', 'White Gold', 39.0, 30, 850000.00, 425000.00, 65.0, FALSE, 'Active'),
('AP-ROO-001', 'Royal Oak Offshore', 3, 2, '26470ST.OO.A027CA.01', 'Automatic', 'Stainless Steel', 42.0, 100, 750000.00, 375000.00, 185.0, FALSE, 'Active'),
('OMG-SPD-001', 'Speedmaster Professional', 4, 2, '310.30.42.50.01.001', 'Mechanical', 'Stainless Steel', 42.0, 50, 165000.00, 82500.00, 155.0, FALSE, 'Active'),
('TAG-CAR-001', 'Carrera Chronograph', 5, 2, 'CBG2A1Z.FT6157', 'Automatic', 'Stainless Steel', 45.0, 100, 95000.00, 47500.00, 180.0, FALSE, 'Active'),
('CAR-SAN-001', 'Santos de Cartier', 7, 3, 'WSSA0029', 'Automatic', 'Stainless Steel', 39.8, 100, 195000.00, 97500.00, 125.0, FALSE, 'Active'),
('IWC-PIL-001', 'Pilot Watch Mark XVIII', 8, 2, 'IW327015', 'Automatic', 'Stainless Steel', 40.0, 60, 125000.00, 62500.00, 145.0, FALSE, 'Active'),
('JLC-REV-001', 'Reverso Classic', 9, 3, 'Q2518410', 'Mechanical', 'Stainless Steel', 42.8, 30, 285000.00, 142500.00, 75.0, FALSE, 'Active'),
('PAN-LUM-001', 'Luminor Marina', 10, 2, 'PAM01312', 'Automatic', 'Stainless Steel', 44.0, 300, 215000.00, 107500.00, 165.0, FALSE, 'Active');

-- Insert Inventory Locations
INSERT INTO inventory_locations (location_code, name, type, address, city, country, capacity, status) VALUES
('IST-001', 'Istanbul Merkez Mağaza', 'Store', 'Nişantaşı, Teşvikiye Cad. No:15', 'Istanbul', 'Turkey', 500, 'Active'),
('IST-002', 'Istanbul Boutique', 'Boutique', 'Zorlu Center, Levazım Mah.', 'Istanbul', 'Turkey', 200, 'Active'),
('ANK-001', 'Ankara Mağaza', 'Store', 'Çankaya, Tunalı Hilmi Cad. No:85', 'Ankara', 'Turkey', 300, 'Active'),
('IZM-001', 'İzmir Mağaza', 'Store', 'Alsancak, Kıbrıs Şehitleri Cad. No:140', 'Izmir', 'Turkey', 250, 'Active'),
('WH-001', 'Ana Depo', 'Warehouse', 'Hadımköy OSB, 1. Cad. No:25', 'Istanbul', 'Turkey', 2000, 'Active'),
('ONL-001', 'Online Mağaza', 'Online', 'Sanal Mağaza', 'Istanbul', 'Turkey', 1000, 'Active');

-- Insert Initial Inventory
INSERT INTO inventory (product_id, location_id, quantity_on_hand, quantity_reserved, reorder_level, max_stock_level, cost_per_unit) VALUES
(1, 1, 15, 2, 5, 25, 142500.00),
(1, 2, 8, 1, 3, 15, 142500.00),
(1, 5, 45, 5, 20, 100, 142500.00),
(2, 1, 12, 1, 5, 20, 160000.00),
(2, 2, 6, 0, 3, 12, 160000.00),
(2, 5, 35, 3, 15, 80, 160000.00),
(3, 1, 3, 0, 2, 8, 425000.00),
(3, 2, 2, 1, 1, 5, 425000.00),
(3, 5, 12, 2, 5, 25, 425000.00),
(4, 1, 8, 1, 3, 15, 375000.00),
(4, 2, 4, 0, 2, 10, 375000.00),
(4, 5, 25, 2, 10, 50, 375000.00),
(5, 1, 20, 3, 8, 35, 82500.00),
(5, 3, 15, 1, 6, 25, 82500.00),
(5, 5, 60, 5, 25, 120, 82500.00),
(6, 1, 25, 2, 10, 40, 47500.00),
(6, 3, 18, 1, 8, 30, 47500.00),
(6, 4, 12, 0, 5, 20, 47500.00),
(7, 1, 10, 1, 4, 18, 97500.00),
(7, 2, 6, 0, 3, 12, 97500.00),
(8, 1, 18, 2, 7, 30, 62500.00),
(8, 3, 14, 1, 6, 25, 62500.00),
(9, 1, 5, 0, 2, 10, 142500.00),
(9, 2, 3, 1, 1, 6, 142500.00),
(10, 1, 12, 1, 5, 20, 107500.00),
(10, 4, 8, 0, 3, 15, 107500.00);

-- Insert Stores
INSERT INTO stores (store_code, name, type, address, city, country, manager_name, phone, email, opening_date, status) VALUES
('ST-IST-001', 'Nişantaşı Flagship Store', 'Physical', 'Nişantaşı, Teşvikiye Cad. No:15', 'Istanbul', 'Turkey', 'Mehmet Yılmaz', '+90 212 555 0101', 'nisantasi@luxurywatch.com', '2020-01-15', 'Active'),
('ST-IST-002', 'Zorlu Center Boutique', 'Boutique', 'Zorlu Center AVM', 'Istanbul', 'Turkey', 'Ayşe Kaya', '+90 212 555 0102', 'zorlu@luxurywatch.com', '2021-03-20', 'Active'),
('ST-ANK-001', 'Ankara Çankaya Store', 'Physical', 'Çankaya, Tunalı Hilmi Cad.', 'Ankara', 'Turkey', 'Ali Demir', '+90 312 555 0201', 'ankara@luxurywatch.com', '2020-06-10', 'Active'),
('ST-IZM-001', 'İzmir Alsancak Store', 'Physical', 'Alsancak, Kıbrıs Şehitleri Cad.', 'Izmir', 'Turkey', 'Fatma Özkan', '+90 232 555 0301', 'izmir@luxurywatch.com', '2021-01-05', 'Active'),
('ST-ONL-001', 'Online Store', 'Online', 'E-commerce Platform', 'Istanbul', 'Turkey', 'Emre Şahin', '+90 212 555 0001', 'online@luxurywatch.com', '2019-12-01', 'Active');

-- Insert Sample Customers
INSERT INTO customers (customer_code, first_name, last_name, email, phone, birth_date, gender, nationality, customer_tier, registration_date, status) VALUES
('CUST-000001', 'Ahmet', 'Yılmaz', 'ahmet.yilmaz@email.com', '+90 532 111 1111', '1985-03-15', 'M', 'Turkish', 'Gold', '2020-01-20', 'Active'),
('CUST-000002', 'Elif', 'Kaya', 'elif.kaya@email.com', '+90 533 222 2222', '1990-07-22', 'F', 'Turkish', 'Platinum', '2019-11-10', 'VIP'),
('CUST-000003', 'John', 'Smith', 'john.smith@email.com', '+1 555 333 3333', '1978-12-05', 'M', 'American', 'Diamond', '2020-05-15', 'VIP'),
('CUST-000004', 'Maria', 'Garcia', 'maria.garcia@email.com', '+34 666 444 4444', '1982-09-18', 'F', 'Spanish', 'Silver', '2021-02-28', 'Active'),
('CUST-000005', 'Hans', 'Mueller', 'hans.mueller@email.com', '+49 177 555 5555', '1975-11-30', 'M', 'German', 'Gold', '2020-08-12', 'Active'),
('CUST-000006', 'Sophie', 'Dubois', 'sophie.dubois@email.com', '+33 6 66 66 66 66', '1988-04-25', 'F', 'French', 'Platinum', '2021-01-18', 'Active'),
('CUST-000007', 'Kemal', 'Özkan', 'kemal.ozkan@email.com', '+90 534 777 7777', '1992-06-08', 'M', 'Turkish', 'Bronze', '2021-09-05', 'Active'),
('CUST-000008', 'Anna', 'Rossi', 'anna.rossi@email.com', '+39 333 888 8888', '1986-01-12', 'F', 'Italian', 'Silver', '2020-12-03', 'Active'),
('CUST-000009', 'David', 'Johnson', 'david.johnson@email.com', '+44 7700 999999', '1980-10-14', 'M', 'British', 'Gold', '2020-04-22', 'Active'),
('CUST-000010', 'Yuki', 'Tanaka', 'yuki.tanaka@email.com', '+81 90 1010 1010', '1983-08-07', 'F', 'Japanese', 'Platinum', '2021-06-30', 'Active');
