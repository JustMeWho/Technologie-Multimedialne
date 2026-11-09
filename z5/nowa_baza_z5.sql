-- ============================================================================
-- Zadanie 5 (z5) - MyCloud, dysk sieciowy
-- Baza: TU_NAZWA_BAZY (InfinityFree, MySQL/MariaDB)
-- Wspolna baza z z1-z4. Ponizsze zmiany rozszerzaja istniejace tabele.
-- ============================================================================

-- 1) Rejestr logowan: rozszerzenie tabeli goscieportalu o login i wynik proby.
ALTER TABLE goscieportalu
    ADD COLUMN username varchar(128) DEFAULT NULL AFTER language,
    ADD COLUMN result   varchar(20)  DEFAULT NULL AFTER username,
    ADD KEY idx_user (username);

-- result przyjmuje wartosci: 'success' (pomyslne), 'failure' (bledne),
-- NULL dla starszych wpisow/wizyt portalu.

-- 2) Konta testowe (hasla hashowane przez PHP password_hash()).
--    admin/TU_HASLO istnieje z z4; test1 / TU_HASLO, test2 / TU_HASLO.

-- ============================================================================
-- Struktura plikow (dysk sieciowy):
--   htdocs/z5/files/<login>/...        - katalog macierzysty kazdego uzytkownika
--   htdocs/z5/files/.htaccess          - blokada bezposredniego dostepu HTTP
--   Pliki sa pobierane/podgladane wylacznie przez download.php (sesja + walidacja
--   sciezki, ochrona przed path traversal).
-- ============================================================================

-- 3) Udostepnianie plikow i katalogow innym uzytkownikom aplikacji.
--    owner  - login wlasciciela elementu,
--    path   - sciezka wzgledna w katalogu macierzystym wlasciciela,
--    target - login odbiorcy udostepnienia.
--    Udostepnienie katalogu obejmuje rowniez wszystkie elementy w nim zawarte
--    (weryfikacja prefiksu sciezki w funkcji share_granted()).
CREATE TABLE IF NOT EXISTS udostepnienia (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner      VARCHAR(32)  NOT NULL,
    path       VARCHAR(768) NOT NULL,
    target     VARCHAR(32)  NOT NULL,
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_owner (owner),
    KEY idx_target (target),
    KEY idx_owner_path (owner, path(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
