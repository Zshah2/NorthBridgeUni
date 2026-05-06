<?php

declare(strict_types=1);

/**
 * One-shot catalog seed: at least five realistic courses per department (Biology, Chemistry,
 * Computer Science, Economics, Engineering, English, History, Philosophy) with descriptions,
 * credits, departments, and prerequisite chains. Safe to re-run (UPSERT courses, INSERT IGNORE prereqs).
 *
 * Run: php scripts/migrate.php && php scripts/seed_full_catalog.php
 *
 * Requires departments from import (department.csv). Optionally runs enrich_catalog for any
 * remaining imported courses still missing descriptions.
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/enrich_catalog.php';

$pdo = db();

/** @var list<array{0: string, 1: string, 2: int, 3: string, 4: string}> */
$courses = [
    // Biology (BIO)
    ['BIO101', 'Introduction to Biology I', 4, 'BIO', 'Cell structure, genetics, and evolution with weekly laboratories emphasizing microscopy, experimental design, and scientific writing. Satisfies natural science core requirements for science and pre-health tracks.'],
    ['BIO102', 'Introduction to Biology II', 4, 'BIO', 'Continuation of organismal biology, physiology, and ecology. Field and lab exercises build data analysis skills and prepare students for upper-division biology coursework.'],
    ['BIO201', 'Genetics and Molecular Biology', 4, 'BIO', 'Mendelian and molecular genetics, gene expression, population genetics, and modern genomic tools. Lecture and integrated problem sessions with laboratory modules.'],
    ['BIO205', 'Ecology and Evolution', 4, 'BIO', 'Population and community ecology, evolutionary theory, and conservation applications. Includes quantitative exercises and short field projects.'],
    ['BIO301', 'Cell Biology', 3, 'BIO', 'Advanced study of organelles, signaling, cell cycle, and cancer biology at the molecular level. Primary literature discussions and presentations.'],

    // Chemistry (CHE)
    ['CHE101', 'General Chemistry I', 4, 'CHE', 'Stoichiometry, atomic theory, bonding, gases, thermochemistry, and solutions. Laboratory emphasizes quantitative techniques and safety.'],
    ['CHE102', 'General Chemistry II', 4, 'CHE', 'Equilibrium, acids and bases, electrochemistry, kinetics, and introductory thermodynamics. Laboratory continues skill-building in analysis.'],
    ['CHE201', 'Organic Chemistry I', 4, 'CHE', 'Structure and reactivity of organic compounds, stereochemistry, and substitution mechanisms. Laboratory introduces synthesis and spectroscopic characterization.'],
    ['CHE205', 'Organic Chemistry II', 4, 'CHE', 'Carbonyl chemistry, aromatic reactions, biomolecules, and retrosynthesis strategies. Laboratory includes multi-step syntheses.'],
    ['CHE301', 'Biochemistry I', 3, 'CHE', 'Protein structure, enzyme kinetics, metabolism pathways, and nucleic acids. Designed for chemistry and biology majors heading toward advanced study.'],

    // Computer Science & Mathematics (COM)
    ['COM101', 'Introduction to Programming', 4, 'COM', 'Problem decomposition, control structures, functions, and basic data structures using a modern language. Weekly coding labs and collaborative debugging exercises.'],
    ['COM110', 'Data Structures', 4, 'COM', 'Lists, stacks, queues, trees, hashing, heaps, and graphs with complexity analysis. Programming-intensive assignments emphasizing correctness and testing.'],
    ['COM201', 'Algorithms', 3, 'COM', 'Design paradigms including divide-and-conquer, greedy methods, dynamic programming, and graph algorithms. Proof-oriented homework sets.'],
    ['COM240', 'Database Systems', 3, 'COM', 'Relational model, SQL, normalization, transactions, indexing, and introductory database design. Hands-on projects with a production-style engine.'],
    ['COM301', 'Software Engineering', 3, 'COM', 'Requirements, architecture, version control, testing, CI basics, and team delivery of a medium-sized application with documentation and demos.'],

    // Economics (ECO)
    ['ECO101', 'Principles of Microeconomics', 3, 'ECO', 'Consumer and firm behavior, markets, elasticity, welfare, and market failures. Graphical and algebraic tools with policy-oriented examples.'],
    ['ECO102', 'Principles of Macroeconomics', 3, 'ECO', 'National income, inflation, unemployment, fiscal and monetary policy, and open-economy fundamentals. Data readings and short analytic essays.'],
    ['ECO201', 'Intermediate Microeconomic Theory', 3, 'ECO', 'Formal models of choice, production, general equilibrium, and welfare economics. Problem sets emphasize calculus-based reasoning.'],
    ['ECO210', 'Introduction to Econometrics', 4, 'ECO', 'Probability review, OLS, inference, heteroskedasticity, and introduction to time series with statistical software labs using real datasets.'],
    ['ECO301', 'International Economics', 3, 'ECO', 'Trade theory, tariffs and quotas, exchange rates, balance of payments, and open-economy macro policy debates.'],

    // Engineering (ENG)
    ['ENG110', 'Introduction to Engineering Design', 3, 'ENG', 'Design process, teamwork, CAD fundamentals, prototyping, ethics, and communication. Culminates in a small team design-build-test project.'],
    ['ENG205', 'Engineering Thermodynamics', 4, 'ENG', 'First and second laws, properties of substances, cycles, and efficiency analysis for thermal-fluid systems relevant to mechanical engineering practice.'],
    ['ENG301', 'Fluid Mechanics', 4, 'ENG', 'Statics and dynamics of fluids, Bernoulli and momentum equations, dimensional analysis, pipe flow, and introductory turbomachinery.'],
    ['ENG310', 'Systems Dynamics and Control', 3, 'ENG', 'Modeling mechanical and electrical systems, transfer functions, stability, PID concepts, and introductory feedback design with simulations.'],
    ['ENG401', 'Senior Capstone Design', 4, 'ENG', 'Year-long style semester project integrating analysis, design constraints, verification, documentation, and formal presentation to faculty and stakeholders.'],

    // English (ENGL)
    ['ENGL101', 'English Composition I', 4, 'ENGL', 'Reading critically, drafting and revising essays, developing arguments, and integrating sources responsibly with emphasis on clarity and audience.'],
    ['ENGL102', 'English Composition II', 4, 'ENGL', 'Research-based writing, synthesis across sources, rhetoric, and revision strategies beyond first-year composition expectations.'],
    ['ENGL201', 'Technical and Professional Writing', 3, 'ENGL', 'Genre conventions for proposals, reports, instructions, and presentations suited to STEM and professional careers with peer review workshops.'],
    ['ENGL210', 'Survey of British Literature', 3, 'ENGL', 'Major authors and movements from the medieval period through the long eighteenth century with attention to historical context and close reading.'],
    ['ENGL301', 'Shakespeare', 3, 'ENGL', 'Representative comedies, histories, and tragedies with emphasis on language, performance history, and critical interpretation.'],

    // History (HIS)
    ['HIS101', 'World Civilizations I', 3, 'HIS', 'Global developments from antiquity through the early modern era using primary sources and comparative themes across regions.'],
    ['HIS102', 'World Civilizations II', 3, 'HIS', 'Modern world history from empires and revolutions through globalization with emphasis on connections and historiographical debates.'],
    ['HIS201', 'United States History to 1877', 3, 'HIS', 'Colonial foundations, revolution, expansion, sectional conflict, and reconstruction with analysis of diverse voices and interpretations.'],
    ['HIS210', 'The Cold War', 3, 'HIS', 'Ideologies, crises, decolonization, and cultural dimensions of the superpower rivalry from 1945 through 1991.'],
    ['HIS301', 'Historiography and Methods', 3, 'HIS', 'How historians construct arguments, use archives, and debate evidence; students produce a substantial research prospectus.'],

    // Philosophy (PHI)
    ['PHI101', 'Introduction to Philosophy', 3, 'PHI', 'Classic problems in metaphysics, epistemology, and ethics through primary texts and structured classroom debate.'],
    ['PHI102', 'Ethics', 3, 'PHI', 'Major normative theories applied to contemporary issues including justice, rights, and professional responsibility.'],
    ['PHI201', 'Symbolic Logic', 3, 'PHI', 'Propositional and predicate logic, proofs, models, and informal fallacies with weekly problem sets.'],
    ['PHI210', 'Political Philosophy', 3, 'PHI', 'Liberty, authority, democracy, and justice from classical through contemporary authors with focused writing assignments.'],
    ['PHI301', 'Metaphysics', 3, 'PHI', 'Identity, causation, modality, and realism debates with attention to recent analytic work and seminar discussion.'],
];

/** @var list<array{0: string, 1: string}> course_id, prereq_course_id */
$prereqs = [
    ['BIO102', 'BIO101'],
    ['BIO201', 'BIO101'],
    ['BIO205', 'BIO102'],
    ['BIO301', 'BIO201'],
    ['CHE102', 'CHE101'],
    ['CHE201', 'CHE102'],
    ['CHE205', 'CHE201'],
    ['CHE301', 'CHE205'],
    ['COM110', 'COM101'],
    ['COM201', 'COM110'],
    ['COM240', 'COM201'],
    ['COM301', 'COM240'],
    ['ECO201', 'ECO101'],
    ['ECO210', 'ECO102'],
    ['ECO301', 'ECO201'],
    ['ENG205', 'ENG110'],
    ['ENG301', 'ENG205'],
    ['ENG310', 'ENG301'],
    ['ENG401', 'ENG310'],
    ['ENGL102', 'ENGL101'],
    ['ENGL201', 'ENGL102'],
    ['ENGL210', 'ENGL201'],
    ['ENGL301', 'ENGL210'],
    ['HIS102', 'HIS101'],
    ['HIS201', 'HIS102'],
    ['HIS210', 'HIS201'],
    ['HIS301', 'HIS210'],
    ['PHI102', 'PHI101'],
    ['PHI201', 'PHI102'],
    ['PHI210', 'PHI201'],
    ['PHI301', 'PHI210'],
];

$requiredDepts = ['BIO', 'CHE', 'COM', 'ECO', 'ENG', 'ENGL', 'HIS', 'PHI'];
$missing = [];
foreach ($requiredDepts as $d) {
    $st = $pdo->prepare('SELECT 1 FROM departments WHERE dept_id = ? LIMIT 1');
    $st->execute([$d]);
    if (!$st->fetchColumn()) {
        $missing[] = $d;
    }
}
if ($missing !== []) {
    fwrite(STDERR, 'seed_full_catalog: missing departments in DB: ' . implode(', ', $missing) . "\n");
    fwrite(STDERR, "Run: php scripts/import_all.php (imports department.csv) then retry.\n");
    exit(1);
}

$pdo->beginTransaction();
try {
    $ins = $pdo->prepare('
      INSERT INTO courses (course_id, course_name, description, credits, dept_id, is_active)
      VALUES (?, ?, ?, ?, ?, 1)
      ON DUPLICATE KEY UPDATE
        course_name = VALUES(course_name),
        credits = VALUES(credits),
        dept_id = VALUES(dept_id),
        description = VALUES(description),
        is_active = VALUES(is_active)
    ');

    foreach ($courses as $row) {
        [$cid, $name, $cr, $dept, $desc] = $row;
        $ins->execute([$cid, $name, $desc, $cr, $dept]);
    }

    $cp = $pdo->prepare('INSERT IGNORE INTO course_prereqs (course_id, prereq_course_id) VALUES (?, ?)');
    foreach ($prereqs as [$c, $p]) {
        $cp->execute([$c, $p]);
    }

    $pdo->commit();
    fwrite(STDOUT, 'seed_full_catalog: upserted ' . count($courses) . ' courses and inserted prerequisite links (ignored if duplicates).' . "\n");
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'seed_full_catalog failed: ' . $e->getMessage() . "\n");
    exit(1);
}

try {
    $stats = enrich_catalog_run($pdo, false);
    fwrite(STDOUT, 'enrich_catalog (remaining gaps): ' . $stats['descriptions'] . ' description(s), ' . $stats['prereqs'] . ' prereq link(s).' . "\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'enrich_catalog non-fatal: ' . $e->getMessage() . "\n");
}

fwrite(STDOUT, "seed_full_catalog: done.\n");
