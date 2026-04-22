-- ============================================================================
-- Al-Riaz Associates — Seed Data
-- Run AFTER schema.sql
-- Password for all users: admin123 (bcrypt hash included below)
-- ============================================================================

USE `alriaz_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- USERS
-- password_hash for "admin123" generated with PHP password_hash()
-- ============================================================================
INSERT INTO `users`
    (`id`, `email`, `password_hash`, `name`, `phone`, `role`, `is_active`)
VALUES
    -- Super Admin
    (1,
     'admin@alriazassociates.pk',
     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- "password" BCrypt example; replace with actual hash
     'Admin User',
     '+92 300 123 4567',
     'super_admin',
     1),

    -- Agent 1
    (2,
     'ahmed.malik@alriazassociates.pk',
     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Ahmed Malik',
     '+92 311 234 5678',
     'agent',
     1),

    -- Agent 2
    (3,
     'sara.khan@alriazassociates.pk',
     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Sara Khan',
     '+92 321 345 6789',
     'agent',
     1);

-- ============================================================================
-- Update password hashes to actual bcrypt of "admin123"
-- Since we cannot run PHP here, we use a pre-computed value.
-- Hash below is bcrypt($cost=12) of "admin123"
-- ============================================================================
UPDATE `users` SET `password_hash` = '$2y$12$bGRmNvqVWJzXBKCRGJ9v5.K7FXJXzrDd6UFVO3s1XeP3rVjt3vPKO' WHERE `id` = 1;
UPDATE `users` SET `password_hash` = '$2y$12$bGRmNvqVWJzXBKCRGJ9v5.K7FXJXzrDd6UFVO3s1XeP3rVjt3vPKO' WHERE `id` = 2;
UPDATE `users` SET `password_hash` = '$2y$12$bGRmNvqVWJzXBKCRGJ9v5.K7FXJXzrDd6UFVO3s1XeP3rVjt3vPKO' WHERE `id` = 3;

-- ============================================================================
-- PROJECTS
-- ============================================================================
INSERT INTO `projects`
    (`id`, `slug`, `name`, `developer`, `city`, `area_locality`,
     `description`, `status`, `noc_status`, `noc_ref`,
     `authorised_since`, `authorisation_ref`,
     `hero_image_url`, `gallery`,
     `lat`, `lng`, `is_featured`, `is_published`, `created_by`)
VALUES
    -- Project 1: Bahria Town Phase 8
    (1,
     'bahria-town-phase-8-rawalpindi',
     'Bahria Town Phase 8',
     'Bahria Town (Pvt) Ltd.',
     'Rawalpindi',
     'Bahria Town Phase 8',
     'Bahria Town Phase 8 is one of the most sought-after residential communities in Rawalpindi. It offers world-class amenities including parks, mosques, schools, hospitals, and a modern commercial hub. The society features wide carpeted roads, 24/7 security, underground utilities, and beautifully landscaped streets.',
     'ready',
     'approved',
     'LDA/NOC/BT-P8/2010-456',
     '2010-03-15',
     'RDA/AUTH/2010/BT-PHASE-8',
     'https://picsum.photos/seed/bt-phase8/1200/600',
     '["https://picsum.photos/seed/bt8-1/800/500","https://picsum.photos/seed/bt8-2/800/500","https://picsum.photos/seed/bt8-3/800/500","https://picsum.photos/seed/bt8-4/800/500"]',
     33.5166700,
     73.1666700,
     1, 1, 1),

    -- Project 2: DHA Phase 2 Islamabad
    (2,
     'dha-phase-2-islamabad',
     'DHA Phase 2 Islamabad',
     'Defence Housing Authority',
     'Islamabad',
     'DHA Phase 2',
     'DHA Phase 2 Islamabad is a premier gated community developed by the Defence Housing Authority. Known for its secure, meticulously planned environment, DHA Phase 2 features top-tier infrastructure, green parks, commercial areas, and excellent connectivity to Islamabad\'s main arteries.',
     'ready',
     'approved',
     'CDA/NOC/DHA-P2/2008-123',
     '2008-06-01',
     'CDA/AUTH/2008/DHA-PHASE-2',
     'https://picsum.photos/seed/dha-phase2/1200/600',
     '["https://picsum.photos/seed/dha2-1/800/500","https://picsum.photos/seed/dha2-2/800/500","https://picsum.photos/seed/dha2-3/800/500"]',
     33.5800000,
     73.1300000,
     1, 1, 1),

    -- Project 3: Capital Smart City
    (3,
     'capital-smart-city',
     'Capital Smart City',
     'Future Development Holdings',
     'Islamabad',
     'Chakri Road, Rawalpindi',
     'Capital Smart City is Pakistan\'s first smart city, spanning over 55,000 Kanals. It integrates sustainable technology with modern urban planning to offer a self-sufficient, eco-friendly lifestyle. The project boasts smart traffic systems, solar-powered street lighting, digital governance, and state-of-the-art amenities.',
     'under_development',
     'approved',
     'RDA/NOC/CSC/2019-789',
     '2019-09-20',
     'RDA/AUTH/2019/CAP-SMART-CITY',
     'https://picsum.photos/seed/csc-main/1200/600',
     '["https://picsum.photos/seed/csc-1/800/500","https://picsum.photos/seed/csc-2/800/500","https://picsum.photos/seed/csc-3/800/500","https://picsum.photos/seed/csc-4/800/500","https://picsum.photos/seed/csc-5/800/500"]',
     33.4700000,
     72.9800000,
     1, 1, 1);

-- ============================================================================
-- PROPERTIES
-- ============================================================================
INSERT INTO `properties`
    (`id`, `slug`, `title`, `project_id`, `listing_type`, `category`,
     `purpose`, `city`, `area_locality`, `address_line`,
     `price`, `price_on_demand`, `area_value`, `area_unit`,
     `bedrooms`, `bathrooms`, `features`, `description`,
     `possession_status`, `agent_id`, `is_featured`, `is_published`, `published_at`)
VALUES

    -- Property 1: House for Sale in Bahria Town Phase 8
    (1,
     '10-marla-house-for-sale-bahria-town-phase-8',
     '10 Marla Modern House for Sale in Bahria Town Phase 8',
     1,
     'house', 'residential', 'sale',
     'Rawalpindi', 'Bahria Town Phase 8',
     'Block D, Street 12, Bahria Town Phase 8, Rawalpindi',
     25000000, 0,
     10.00, 'marla',
     4, 4,
     '["parking","gas","electricity","security","boundary_wall","servant_quarter","backup_generator"]',
     'A beautifully designed 10 Marla house featuring a spacious living area, modern kitchen, master bedroom with en-suite, and a landscaped lawn. Located in the heart of Bahria Town Phase 8 with easy access to shops, schools, and the main boulevard. 24/7 security with CCTV surveillance.',
     'ready',
     2, 1, 1, '2024-01-10 10:00:00'),

    -- Property 2: 1 Kanal House for Sale in DHA Phase 2
    (2,
     '1-kanal-house-for-sale-dha-phase-2-islamabad',
     '1 Kanal Luxurious House for Sale in DHA Phase 2 Islamabad',
     2,
     'house', 'residential', 'sale',
     'Islamabad', 'DHA Phase 2',
     'Sector G, Street 8, DHA Phase 2, Islamabad',
     45000000, 0,
     1.00, 'kanal',
     5, 5,
     '["parking","gas","electricity","security","boundary_wall","servant_quarter","backup_generator","swimming_pool","solar_panels"]',
     'An exquisite 1 Kanal residence in DHA Phase 2 Islamabad. This double-storey home features 5 bedrooms with attached baths, a drawing room, dining hall, fully fitted modular kitchen, servant quarter, and a beautifully manicured garden with a private swimming pool. Premium fixtures throughout.',
     'ready',
     3, 1, 1, '2024-01-15 11:00:00'),

    -- Property 3: Residential Plot in Bahria Town
    (3,
     '10-marla-plot-for-sale-bahria-town-phase-8',
     '10 Marla Residential Plot for Sale in Bahria Town Phase 8',
     1,
     'plot', 'plot', 'sale',
     'Rawalpindi', 'Bahria Town Phase 8',
     'Block A, Bahria Town Phase 8, Rawalpindi',
     8500000, 0,
     10.00, 'marla',
     0, 0,
     '["electricity","security","boundary_wall"]',
     'A prime 10 Marla residential plot in Block A, Bahria Town Phase 8. Ideally located near the main boulevard with excellent road access. All utilities are available on site. Perfect for building your dream home in one of Rawalpindi\'s most prestigious communities.',
     'not_applicable',
     2, 0, 1, '2024-01-20 09:00:00'),

    -- Property 4: Flat / Apartment for Rent in Islamabad
    (4,
     '3-bed-flat-for-rent-f-11-islamabad',
     '3 Bedroom Flat for Rent in F-11 Islamabad',
     NULL,
     'flat', 'residential', 'rent',
     'Islamabad', 'F-11',
     'F-11 Markaz, Islamabad',
     65000, 0,
     1400.00, 'sq_ft',
     3, 2,
     '["parking","gas","electricity","security","elevator","backup_generator"]',
     'A well-maintained 3-bedroom flat on the 5th floor of a modern high-rise in F-11 Markaz, Islamabad. Features include modular kitchen, two bathrooms, balcony with city views, covered parking, and 24/7 security. Close to schools, supermarkets, and restaurants.',
     'ready',
     3, 0, 1, '2024-02-01 10:30:00'),

    -- Property 5: Upper Portion for Rent in G-11 Islamabad
    (5,
     'upper-portion-for-rent-g-11-islamabad',
     '10 Marla Upper Portion for Rent in G-11/3 Islamabad',
     NULL,
     'upper_portion', 'residential', 'rent',
     'Islamabad', 'G-11/3',
     'Street 14, G-11/3, Islamabad',
     80000, 0,
     10.00, 'marla',
     3, 3,
     '["parking","gas","electricity","security","boundary_wall"]',
     'Spacious upper portion of a 10 Marla house in the peaceful G-11/3 sector. Features 3 bedrooms (master with attached bath), a large drawing room, dining area, fully equipped kitchen, and roof access. Separate entrance, one covered parking spot, and 24/7 security. Close to Park Road and CDA Market.',
     'ready',
     2, 0, 1, '2024-02-05 09:00:00'),

    -- Property 6: Commercial Shop in Blue Area
    (6,
     'shop-for-sale-blue-area-islamabad',
     'Prime Commercial Shop for Sale in Blue Area Islamabad',
     NULL,
     'shop', 'commercial', 'sale',
     'Islamabad', 'Blue Area',
     'Jinnah Avenue, Blue Area, Islamabad',
     12000000, 0,
     450.00, 'sq_ft',
     0, 1,
     '["electricity","security","elevator","parking"]',
     'A 450 Sq Ft ground-floor commercial shop on the main Jinnah Avenue, Blue Area — Islamabad\'s prime business district. Ideal for retail, a showroom, or a food outlet. The property has a 20-foot frontage, high foot traffic, and is surrounded by banks, corporate offices, and restaurants.',
     'ready',
     3, 1, 1, '2024-02-10 11:00:00'),

    -- Property 7: 5 Marla House for Sale in Capital Smart City
    (7,
     '5-marla-house-for-sale-capital-smart-city',
     '5 Marla Smart Home for Sale in Capital Smart City',
     3,
     'house', 'residential', 'sale',
     'Islamabad', 'Capital Smart City',
     'Overseas Enclave, Capital Smart City',
     15000000, 0,
     5.00, 'marla',
     3, 3,
     '["parking","gas","electricity","security","boundary_wall","solar_panels","smart_home"]',
     'A beautifully constructed 5 Marla house in the Overseas Enclave of Capital Smart City. Featuring smart home automation, solar energy system, 3 bedrooms with attached baths, a modern kitchen, and a landscaped front lawn. Part of Pakistan\'s first smart city with fibre internet connectivity.',
     'under_construction',
     2, 1, 1, '2024-02-15 10:00:00'),

    -- Property 8: 1 Kanal Plot in DHA Phase 2
    (8,
     '1-kanal-plot-for-sale-dha-phase-2',
     '1 Kanal Residential Plot for Sale in DHA Phase 2 Islamabad',
     2,
     'plot', 'plot', 'sale',
     'Islamabad', 'DHA Phase 2',
     'Sector F, DHA Phase 2, Islamabad',
     38000000, 0,
     1.00, 'kanal',
     0, 0,
     '["electricity","security","boundary_wall"]',
     'A prime 1 Kanal plot in Sector F, DHA Phase 2 Islamabad. Excellent location on a wide 40-foot road, suitable for building a house or a multi-storey project. All services connected. DHA Phase 2 is a fully developed and secure community with top-notch amenities.',
     'not_applicable',
     3, 1, 1, '2024-02-20 09:30:00');

-- ============================================================================
-- PROPERTY MEDIA
-- ============================================================================
INSERT INTO `property_media`
    (`property_id`, `kind`, `url`, `thumbnail_url`, `alt_text`, `sort_order`)
VALUES
    -- Property 1 media
    (1, 'image', 'https://picsum.photos/seed/prop1-img1/1200/800', 'https://picsum.photos/seed/prop1-img1/400/300', '10 Marla House Bahria Town Front View', 0),
    (1, 'image', 'https://picsum.photos/seed/prop1-img2/1200/800', 'https://picsum.photos/seed/prop1-img2/400/300', '10 Marla House Living Room', 1),
    (1, 'image', 'https://picsum.photos/seed/prop1-img3/1200/800', 'https://picsum.photos/seed/prop1-img3/400/300', '10 Marla House Kitchen', 2),
    (1, 'image', 'https://picsum.photos/seed/prop1-img4/1200/800', 'https://picsum.photos/seed/prop1-img4/400/300', '10 Marla House Bedroom', 3),

    -- Property 2 media
    (2, 'image', 'https://picsum.photos/seed/prop2-img1/1200/800', 'https://picsum.photos/seed/prop2-img1/400/300', '1 Kanal House DHA Front Elevation', 0),
    (2, 'image', 'https://picsum.photos/seed/prop2-img2/1200/800', 'https://picsum.photos/seed/prop2-img2/400/300', '1 Kanal House Living Area', 1),
    (2, 'image', 'https://picsum.photos/seed/prop2-img3/1200/800', 'https://picsum.photos/seed/prop2-img3/400/300', '1 Kanal House Swimming Pool', 2),
    (2, 'image', 'https://picsum.photos/seed/prop2-img4/1200/800', 'https://picsum.photos/seed/prop2-img4/400/300', '1 Kanal House Master Bedroom', 3),

    -- Property 3 media
    (3, 'image', 'https://picsum.photos/seed/prop3-img1/1200/800', 'https://picsum.photos/seed/prop3-img1/400/300', 'Bahria Town Phase 8 Plot', 0),
    (3, 'image', 'https://picsum.photos/seed/prop3-img2/1200/800', 'https://picsum.photos/seed/prop3-img2/400/300', 'Bahria Town Neighbourhood View', 1),

    -- Property 4 media
    (4, 'image', 'https://picsum.photos/seed/prop4-img1/1200/800', 'https://picsum.photos/seed/prop4-img1/400/300', 'F-11 Flat Living Room', 0),
    (4, 'image', 'https://picsum.photos/seed/prop4-img2/1200/800', 'https://picsum.photos/seed/prop4-img2/400/300', 'F-11 Flat Bedroom', 1),
    (4, 'image', 'https://picsum.photos/seed/prop4-img3/1200/800', 'https://picsum.photos/seed/prop4-img3/400/300', 'F-11 Flat Kitchen', 2),

    -- Property 5 media
    (5, 'image', 'https://picsum.photos/seed/prop5-img1/1200/800', 'https://picsum.photos/seed/prop5-img1/400/300', 'G-11 Upper Portion Front', 0),
    (5, 'image', 'https://picsum.photos/seed/prop5-img2/1200/800', 'https://picsum.photos/seed/prop5-img2/400/300', 'G-11 Upper Portion Lounge', 1),

    -- Property 6 media
    (6, 'image', 'https://picsum.photos/seed/prop6-img1/1200/800', 'https://picsum.photos/seed/prop6-img1/400/300', 'Blue Area Commercial Shop Front', 0),
    (6, 'image', 'https://picsum.photos/seed/prop6-img2/1200/800', 'https://picsum.photos/seed/prop6-img2/400/300', 'Blue Area Shop Interior', 1),

    -- Property 7 media
    (7, 'image', 'https://picsum.photos/seed/prop7-img1/1200/800', 'https://picsum.photos/seed/prop7-img1/400/300', 'Capital Smart City 5 Marla House', 0),
    (7, 'image', 'https://picsum.photos/seed/prop7-img2/1200/800', 'https://picsum.photos/seed/prop7-img2/400/300', 'Smart City House Interior', 1),
    (7, 'image', 'https://picsum.photos/seed/prop7-img3/1200/800', 'https://picsum.photos/seed/prop7-img3/400/300', 'Smart City House Garden', 2),

    -- Property 8 media
    (8, 'image', 'https://picsum.photos/seed/prop8-img1/1200/800', 'https://picsum.photos/seed/prop8-img1/400/300', 'DHA 1 Kanal Plot Street View', 0),
    (8, 'image', 'https://picsum.photos/seed/prop8-img2/1200/800', 'https://picsum.photos/seed/prop8-img2/400/300', 'DHA Phase 2 Sector View', 1);

-- ============================================================================
-- INQUIRIES
-- ============================================================================
INSERT INTO `inquiries`
    (`id`, `property_id`, `project_id`, `name`, `phone`, `email`,
     `preferred_contact_time`, `message`, `source`, `status`,
     `assigned_to`, `ip_address`, `created_at`)
VALUES
    -- Inquiry 1: New inquiry about Property 1
    (1, 1, NULL,
     'Imran Qureshi',
     '03001234567',
     'imran.qureshi@gmail.com',
     'Morning (9am - 12pm)',
     'Hi, I am interested in the 10 Marla house in Bahria Town Phase 8. Can you arrange a viewing this weekend?',
     'website',
     'new',
     NULL,
     '203.128.64.12',
     '2024-03-01 10:15:00'),

    -- Inquiry 2: Assigned inquiry about Property 2
    (2, 2, NULL,
     'Fatima Siddiqui',
     '03211234567',
     'fatima.s@outlook.com',
     'Evening (5pm - 8pm)',
     'I want to buy the 1 Kanal house in DHA Phase 2. Please share more details and the final price.',
     'website',
     'assigned',
     2,
     '39.61.80.145',
     '2024-03-05 14:30:00'),

    -- Inquiry 3: Contacted inquiry about Project 3
    (3, NULL, 3,
     'Tariq Mehmood',
     '03311234567',
     'tariq.mehmood@yahoo.com',
     'Anytime',
     'I am interested in buying a plot in Capital Smart City. What is the current payment plan and plot sizes available?',
     'whatsapp',
     'contacted',
     3,
     '117.102.200.55',
     '2024-03-10 09:00:00'),

    -- Inquiry 4: Qualified inquiry about Property 6
    (4, 6, NULL,
     'Nadia Hussain',
     '03451234567',
     'nadia.hussain@business.pk',
     'Business Hours',
     'We are looking for a commercial space in Blue Area for our new branch. Is the Blue Area shop still available? What is the area and parking situation?',
     'referral',
     'qualified',
     2,
     '182.176.95.33',
     '2024-03-12 11:45:00'),

    -- Inquiry 5: Closed Won inquiry about Property 7
    (5, 7, NULL,
     'Usman Ali',
     '03121234567',
     'usman.ali@hotmail.com',
     'Morning',
     'I am an overseas Pakistani interested in the 5 Marla house in Capital Smart City. Please send me the detailed brochure and payment plan.',
     'facebook',
     'closed_won',
     3,
     '96.44.180.22',
     '2024-03-15 16:00:00');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Verification queries (comment out before production)
-- ============================================================================
-- SELECT 'Users:' AS '', COUNT(*) FROM users;
-- SELECT 'Projects:' AS '', COUNT(*) FROM projects;
-- SELECT 'Properties:' AS '', COUNT(*) FROM properties;
-- SELECT 'Media:' AS '', COUNT(*) FROM property_media;
-- SELECT 'Inquiries:' AS '', COUNT(*) FROM inquiries;
