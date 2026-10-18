<?php
declare(strict_types=1);

/**
 * Suggestions shown while the user types in the Education cards (HTML <datalist>).
 * They are only HINTS: the user may type any name, and nothing is validated against these lists.
 * The lists are NOT an official register of institutions.
 *
 * Why a fixed list and not "names typed by other users"? A shared list built from free text
 * would need moderation (typos, spam, offensive text) - the same reason "Other skills" was not added.
 */
const SCHOOL_SUGGESTIONS = [
    // Indonesia - state universities
    'Universitas Indonesia', 'Institut Teknologi Bandung', 'Universitas Gadjah Mada', 'Institut Pertanian Bogor',
    'Institut Teknologi Sepuluh Nopember', 'Universitas Airlangga', 'Universitas Diponegoro', 'Universitas Padjadjaran',
    'Universitas Brawijaya', 'Universitas Sebelas Maret', 'Universitas Hasanuddin', 'Universitas Sumatera Utara',
    'Universitas Andalas', 'Universitas Syiah Kuala', 'Universitas Negeri Yogyakarta', 'Universitas Negeri Malang',
    'Universitas Pendidikan Indonesia', 'Universitas Negeri Semarang', 'Universitas Negeri Jakarta', 'Universitas Udayana',
    'Universitas Jember', 'Universitas Lampung', 'Universitas Sriwijaya', 'Universitas Riau', 'Universitas Mulawarman',
    // Indonesia - private universities
    'Binus University', 'Telkom University', 'Universitas Pelita Harapan', 'Universitas Trisakti',
    'Universitas Islam Indonesia', 'Universitas Muhammadiyah Yogyakarta', 'Universitas Atma Jaya Yogyakarta',
    'Universitas Kristen Petra', 'Universitas Gunadarma', 'Universitas Mercu Buana', 'Universitas Multimedia Nusantara',
    'Universitas Ciputra', 'Universitas Katolik Parahyangan',
    // Indonesia - polytechnics
    'Politeknik Negeri Bandung', 'Politeknik Negeri Jakarta', 'Politeknik Elektronika Negeri Surabaya', 'Politeknik Negeri Malang',
    // Other
    'Asia e University', 'Universiti Malaya', 'Universiti Teknologi Malaysia', 'National University of Singapore',
    'Nanyang Technological University', 'Monash University',
];

const FIELD_SUGGESTIONS = [
    'Informatics', 'Computer Science', 'Information Systems', 'Information Technology', 'Software Engineering',
    'Data Science', 'Electrical Engineering', 'Mechanical Engineering', 'Civil Engineering', 'Industrial Engineering',
    'Chemical Engineering', 'Architecture', 'Accounting', 'Management', 'Business Administration', 'Digital Business',
    'Economics', 'Law', 'Psychology', 'Communication Science', 'Visual Communication Design', 'Product Design',
    'International Relations', 'Nursing', 'Pharmacy', 'Medicine', 'Public Health', 'Biology', 'Chemistry', 'Physics',
    'Mathematics', 'Statistics', 'Agriculture', 'Education', 'English Literature', 'Tourism', 'Hospitality Management',
];
