CREATE TABLE diagnoses (
  id int(11) NOT NULL,
  name varchar(255) NOT NULL
) ;

-- --------------------------------------------------------



CREATE TABLE diagnosis_notes (
  id int(11) NOT NULL,
  person_id int(11) NOT NULL,
  diagnosis_id int(11) NOT NULL,
  note text NOT NULL,
  created_at datetime DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table medical_reports
--

CREATE TABLE medical_reports (
  id int(11) NOT NULL,
  person_id int(11) NOT NULL,
  report_text text NOT NULL,
  created_at datetime NOT NULL,
  diagnosis_id int(11) NOT NULL,
  diagnosis_note_id int(11) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table persons
--

CREATE TABLE persons (
  id int(11) NOT NULL,
  first_name varchar(100) DEFAULT NULL,
  surname varchar(100) DEFAULT NULL,
  birth_date date DEFAULT NULL,
  ssn varchar(20) DEFAULT NULL,
  medications varchar(255) DEFAULT NULL,
  allergies varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table persons_backup
--

CREATE TABLE persons_backup (
  id int(11) NOT NULL DEFAULT 0,
  first_name varchar(100) DEFAULT NULL,
  surname varchar(100) DEFAULT NULL,
  birth_date date DEFAULT NULL,
  ssn varchar(20) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table person_diagnoses
--

CREATE TABLE person_diagnoses (
  id int(11) NOT NULL,
  person_id int(11) NOT NULL,
  diagnosis_id int(11) NOT NULL,
  assigned_at date DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table templates
--

CREATE TABLE templates (
  id int(11) NOT NULL,
  diagnosis_id int(11) NOT NULL,
  template_text text NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table tick_bites
--

CREATE TABLE tick_bites (
  id int(11) NOT NULL,
  person_id int(11) NOT NULL,
  x double NOT NULL,
  y double NOT NULL,
  created_at datetime NOT NULL
  bite_order INT DEFAULT 1 
  ) ;

-- --------------------------------------------------------

--
-- Table structure for table users
--

CREATE TABLE users (
  id int(11) NOT NULL,
  username varchar(255) DEFAULT NULL,
  password varchar(255) DEFAULT NULL,
  role enum('admin','user') DEFAULT 'user',
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table diagnoses
--
ALTER TABLE diagnoses
  ADD PRIMARY KEY (id);

--
-- Indexes for table diagnosis_notes
--
ALTER TABLE diagnosis_notes
  ADD PRIMARY KEY (id),
  ADD KEY diagnosis_id (diagnosis_id),
  ADD KEY fk_diagnosis_notes_person (person_id);

--
-- Indexes for table medical_reports
--
ALTER TABLE medical_reports
  ADD PRIMARY KEY (id),
  ADD KEY fk_medical_reports_diagnosis_note (diagnosis_note_id),
  ADD KEY fk_medical_reports_person (person_id);

--
-- Indexes for table persons
--
ALTER TABLE persons
  ADD PRIMARY KEY (id);

--
-- Indexes for table person_diagnoses
--
ALTER TABLE person_diagnoses
  ADD PRIMARY KEY (id),
  ADD KEY diagnosis_id (diagnosis_id),
  ADD KEY fk_person_diagnoses_person (person_id);

--
-- Indexes for table templates
--
ALTER TABLE templates
  ADD PRIMARY KEY (id),
  ADD KEY diagnosis_id (diagnosis_id);

--
-- Indexes for table tick_bites
--
ALTER TABLE tick_bites
  ADD PRIMARY KEY (id);

--
-- Indexes for table users
--
ALTER TABLE users
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY username (username);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table diagnoses
--
ALTER TABLE diagnoses
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table diagnosis_notes
--
ALTER TABLE diagnosis_notes
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table medical_reports
--
ALTER TABLE medical_reports
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table persons
--
ALTER TABLE persons
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table person_diagnoses
--
ALTER TABLE person_diagnoses
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table templates
--
ALTER TABLE templates
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table tick_bites
--
ALTER TABLE tick_bites
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table users
--
ALTER TABLE users
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table diagnosis_notes
--
ALTER TABLE diagnosis_notes
  ADD CONSTRAINT diagnosis_notes_ibfk_1 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE,
  ADD CONSTRAINT diagnosis_notes_ibfk_2 FOREIGN KEY (diagnosis_id) REFERENCES diagnoses (id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_diagnosis_notes_person FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

--
-- Constraints for table medical_reports
--
ALTER TABLE medical_reports
  ADD CONSTRAINT fk_medical_reports_diagnosis_note FOREIGN KEY (diagnosis_note_id) REFERENCES diagnosis_notes (id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_medical_reports_person FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE,
  ADD CONSTRAINT medical_reports_ibfk_1 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

--
-- Constraints for table person_diagnoses
--
ALTER TABLE person_diagnoses
  ADD CONSTRAINT fk_person_diagnoses_person FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE,
  ADD CONSTRAINT person_diagnoses_ibfk_1 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE,
  ADD CONSTRAINT person_diagnoses_ibfk_2 FOREIGN KEY (diagnosis_id) REFERENCES diagnoses (id) ON DELETE CASCADE;

--
-- Constraints for table templates
--
ALTER TABLE templates
  ADD CONSTRAINT templates_ibfk_1 FOREIGN KEY (diagnosis_id) REFERENCES diagnoses (id) ON DELETE CASCADE;

-- Přidání sloupce updated_by do všech tabulek pro logování uživatele, který provedl změnu

ALTER TABLE diagnoses ADD COLUMN updated_by INT NULL AFTER name;
ALTER TABLE diagnosis_notes ADD COLUMN updated_by INT NULL AFTER created_at;
ALTER TABLE medical_reports ADD COLUMN updated_by INT NULL AFTER created_at;
ALTER TABLE persons ADD COLUMN updated_by INT NULL AFTER allergies;
ALTER TABLE persons_backup ADD COLUMN updated_by INT NULL AFTER ssn;
ALTER TABLE person_diagnoses ADD COLUMN updated_by INT NULL AFTER assigned_at;
ALTER TABLE templates ADD COLUMN updated_by INT NULL AFTER created_at;
ALTER TABLE tick_bites ADD COLUMN updated_by INT NULL AFTER created_at;
ALTER TABLE users ADD COLUMN updated_by INT NULL AFTER created_at;

-- Přidání cizích klíčů (kromě tabulky users)
ALTER TABLE diagnoses ADD CONSTRAINT fk_diagnoses_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE diagnosis_notes ADD CONSTRAINT fk_diagnosis_notes_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE medical_reports ADD CONSTRAINT fk_medical_reports_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE persons ADD CONSTRAINT fk_persons_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE persons_backup ADD CONSTRAINT fk_persons_backup_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE person_diagnoses ADD CONSTRAINT fk_person_diagnoses_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE templates ADD CONSTRAINT fk_templates_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE tick_bites ADD CONSTRAINT fk_tick_bites_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
-- Tabulka users nemá cizí klíč na sebe sama.

-- Přidání sloupců pro jméno a příjmení do tabulky users
ALTER TABLE users
ADD COLUMN firstname VARCHAR(100) NOT NULL AFTER role,
ADD COLUMN lastname VARCHAR(100) NOT NULL AFTER firstname;
