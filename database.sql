CREATE DATABASE IF NOT EXISTS projekt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE projekt;

-- tabulka users
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- testing email=test@skola.sk a heslo=heslo123
INSERT INTO users (name, email, password_hash) VALUES
('test', 'test@skola.sk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),

-- Tabulka otazky
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    author VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Všeobecné',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- tabulka(odpovede)
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    body TEXT NOT NULL,
    author VARCHAR(100) NOT NULL,
    is_accepted TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- otazky a odpovede na testovanie
INSERT INTO questions (title, body, author, category) VALUES
('Ako funguje rekurzia v PHP?', 'Zdravím, potrebujem pochopiť rekurziu. Mohol by mi niekto vysvetliť na príklade, ako funguje rekurzívna funkcia v PHP? Ďakujem!', 'Tomáš Novák', 'PHP'),
('Čo je SQL JOIN a aké typy existujú?', 'Učím sa databázy a narazil som na JOIN. Aký je rozdiel medzi INNER JOIN, LEFT JOIN a RIGHT JOIN? Kedy ktorý použiť?', 'Petra Kováčová', 'Databázy'),
('Bootstrap grid systém - ako funguje?', 'Chcem pochopiť, ako funguje grid systém v Bootstrape. Čo znamená col-md-6 a podobné triedy?', 'Martin Horváth', 'HTML/CSS'),
('Rozdiel medzi GET a POST metódou?', 'V škole sme preberali formuláre. Nechápe mi, aký je praktický rozdiel medzi GET a POST a kedy použiť ktorú metódu.', 'Lucia Slobodová', 'PHP'),
('Ako správne validovať formulár v PHP?', 'Robím projekt a potrebujem vedieť, ako overiť, či užívateľ vyplnil formulár správne. Aké funkcie použiť na sanitizáciu vstupov?', 'Jakub Mináč', 'PHP');

INSERT INTO answers (question_id, body, author, is_accepted) VALUES
(1, 'Rekurzia je keď funkcia volá samú seba. Príklad: function factorial($n) { if ($n <= 1) return 1; return $n * factorial($n - 1); } Volanie factorial(5) vráti 120. Dôležité je mať ukončovaciu podmienku, inak nastane nekonečná slučka!', 'Prof. Krajčí', 1),
(1, 'Ešte by som dodal - rekurzia je síce elegantná, ale pri veľkých hodnotách môže spôsobiť "stack overflow". Pre veľké čísla je lepšie použiť iteráciu (cyklus).', 'Tomáš Novák', 0),
(2, 'INNER JOIN vráti iba riadky, ktoré majú zhodu v oboch tabuľkách. LEFT JOIN vráti všetky riadky z ľavej tabuľky + zhody z pravej (NULL kde niet zhody). RIGHT JOIN je opačný. Najčastejšie sa používa LEFT JOIN.', 'Jana Bednárová', 1),
(3, 'Bootstrap rozdeľuje riadok na 12 stĺpcov. col-md-6 znamená: na obrazovke medium (768px+) zaberie prvok 6 z 12 stĺpcov, teda polovicu šírky. Na mobile (bez md) sa automaticky zobrazí na celú šírku.', 'Prof. Krajčí', 1),
(4, 'GET posiela dáta v URL (viditeľné, bookmarkovateľné, obmedzená dĺžka) - hodí sa na vyhľadávanie. POST posiela dáta skryto v tele požiadavky - pre prihlásenie, formuláre s citlivými dátami. Nikdy neposielajte heslá cez GET!', 'Martin Horváth', 1),
(5, 'Základné funkcie: htmlspecialchars() na escapovanie HTML, trim() na odstránenie medzier, filter_var() na validáciu emailu/čísel. Vždy sanitizuj PRED uložením do DB a escapuj PRI výpise na stránku.', 'Jana Bednárová', 0),
(5, 'Nezabudni tiež na prepared statements pri práci s MySQL! To je ochrana pred SQL injection. Nikdy nezakladaj query priamo z $_POST premenných.', 'Prof. Krajčí', 1);