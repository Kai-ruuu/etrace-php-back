<?php

require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../../utils/AlumniCsvScanner.php";
require_once __DIR__ . "/../../utils/Storage.php";

class Analytics
{
    protected PDO $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUserAdminAnalytics($role): array
    {
        $query = "
            SELECT
                (SELECT COUNT(id) FROM users WHERE role = ? AND enabled = TRUE) AS active,
                (SELECT COUNT(id) FROM users WHERE role = ?) AS total
        ";
        $statement = $this->pdo->prepare($query);
        $statement->execute([$role, $role]);
        $raw = $statement->fetch();
        return [
            'active' => $raw['active'],
            'inactive' => $raw['total'] - $raw['active'],
            'total' => $raw['total'],
        ];
    }

    public function getUserCompanyAnalytics(): array
    {
        $query = "
            SELECT
                (SELECT COUNT(id) FROM users WHERE role = 'company' AND enabled = TRUE) AS active,
                (SELECT COUNT(id) FROM users WHERE role = 'company') AS total,
                (SELECT COUNT(u.id) FROM users u JOIN companies c ON c.user_id = u.id WHERE role = 'company' AND c.ver_stat_sysad = 'Pending') AS total_sysad_pending,
                (SELECT COUNT(u.id) FROM users u JOIN companies c ON c.user_id = u.id WHERE role = 'company' AND c.ver_stat_sysad = 'Rejected') AS total_sysad_rejected,
                (SELECT COUNT(u.id) FROM users u JOIN companies c ON c.user_id = u.id WHERE role = 'company' AND c.ver_stat_sysad = 'Verified') AS total_sysad_verified,
                (SELECT COUNT(u.id) FROM users u JOIN companies c ON c.user_id = u.id WHERE role = 'company' AND c.ver_stat_pstaff = 'Pending') AS total_pstaff_pending,
                (SELECT COUNT(u.id) FROM users u JOIN companies c ON c.user_id = u.id WHERE role = 'company' AND c.ver_stat_pstaff = 'Rejected') AS total_pstaff_rejected,
                (SELECT COUNT(u.id) FROM users u JOIN companies c ON c.user_id = u.id WHERE role = 'company' AND c.ver_stat_pstaff = 'Verified') AS total_pstaff_verified
        ";
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $raw = $statement->fetch();
        return [
            'active' => $raw['active'],
            'inactive' => $raw['total'] - $raw['active'],
            'total' => $raw['total'],
            'sysad_verification_info' => [
                'pending' => $raw['total_sysad_pending'],
                'rejected' => $raw['total_sysad_rejected'],
                'verified' => $raw['total_sysad_verified'],
            ],
            'pstaff_verification_info' => [
                'pending' => $raw['total_pstaff_pending'],
                'rejected' => $raw['total_pstaff_rejected'],
                'verified' => $raw['total_pstaff_verified'],
            ],
        ];
    }

    public function getUserAlumniAnalytics(): array
    {
        $query = "
            SELECT
                (SELECT COUNT(id) FROM users WHERE role = 'alumni' AND enabled = TRUE) AS active,
                (SELECT COUNT(id) FROM users WHERE role = 'alumni') AS total,
                (SELECT COUNT(u.id) FROM users u JOIN alumni a ON a.user_id = u.id WHERE u.role = 'alumni' AND a.gender = 'Male') AS total_male,
                (SELECT COUNT(u.id) FROM users u JOIN alumni a ON a.user_id = u.id WHERE u.role = 'alumni' AND a.gender = 'Female') AS total_female,
                (SELECT COUNT(u.id) FROM users u JOIN alumni a ON a.user_id = u.id WHERE u.role = 'alumni' AND u.enabled = TRUE AND a.gender = 'Male') AS total_active_male,
                (SELECT COUNT(u.id) FROM users u JOIN alumni a ON a.user_id = u.id WHERE u.role = 'alumni' AND u.enabled = TRUE AND a.gender = 'Female') AS total_active_female,
                (SELECT COUNT(u.id) FROM users u JOIN alumni a ON a.user_id = u.id WHERE u.role = 'alumni' AND a.ver_stat_dean = 'Pending') AS total_pending,
                (SELECT COUNT(u.id) FROM users u JOIN alumni a ON a.user_id = u.id WHERE u.role = 'alumni' AND a.ver_stat_dean = 'Verified') AS total_verified,
                (SELECT COUNT(u.id) FROM users u JOIN alumni a ON a.user_id = u.id WHERE u.role = 'alumni' AND a.ver_stat_dean = 'Rejected') AS total_rejected,
                (SELECT COUNT(id) FROM alumni WHERE employment_status = 'Unemployed' AND ver_stat_dean = 'Verified') AS total_unemployed,
                (SELECT COUNT(id) FROM alumni WHERE employment_status = 'Self-employed' AND ver_stat_dean = 'Verified') AS total_self_employed,
                (SELECT COUNT(id) FROM alumni WHERE employment_status = 'Employed' AND ver_stat_dean = 'Verified') AS total_employed
        ";
        $raw = $this->pdo->query($query)->fetch();
        $formatted = [
            'summary_info' => [
                'total' => $raw['total'],
                'active' => $raw['active'],
                'inactive' => $raw['total'] - $raw['active'],
                'male' => [
                    'active' => $raw['total_active_male'],
                    'inactive' => $raw['total_male'] - $raw['total_active_male'],
                    'total' => $raw['total_male'],
                ],
                'female' => [
                    'active' => $raw['total_active_female'],
                    'inactive' => $raw['total_female'] - $raw['total_active_female'],
                    'total' => $raw['total_female'],
                ],
            ],
            'verification_status_info' => [
                'pending' => $raw['total_pending'],
                'verified' => $raw['total_verified'],
                'rejected' => $raw['total_rejected'],
            ],
            'employment_status_info' => [
                'unemployed' => $raw['total_unemployed'],
                'self_employed' => $raw['total_self_employed'],
                'employed' => $raw['total_employed'],
            ]
        ];

        $recordsQuery = "SELECT filename FROM graduate_records WHERE archived = FALSE";
        $statement = $this->pdo->query($recordsQuery);
        $filenames = $statement->fetchAll(PDO::FETCH_COLUMN);
        $total = 0;
        $total_male = 0;
        $total_female = 0;

        foreach ($filenames as $filename) {
            $filepath = Storage::dest("graduate_records") . "/" . $filename;
            $csvScanner = new AlumniCsvScanner($filepath);
            $summary = $csvScanner->getSummary();
            $total += $summary['total'];
            $total_male += $summary['male'];
            $total_female += $summary['female'];
        }

        $formatted['registration_info'] = [
            'expected' => $total,
            'expected_male' => $total_male,
            'expected_female' => $total_female,
        ];

        $coursesQuery = "SELECT code FROM courses WHERE archived = FALSE";
        $statement = $this->pdo->query($coursesQuery);
        $courseCodes = $statement->fetchAll(PDO::FETCH_COLUMN);
        $courseAlignedOccupations = [];

        foreach ($courseCodes as $code) {
            $withCourseAlignedOccupationsQuery = "
                SELECT
                (
                    SELECT
                        COUNT(DISTINCT a.id)
                        FROM alumni a
                        JOIN courses c ON c.id = a.course_id
                        JOIN occupation_statuses os ON os.alumni_id = a.id
                        JOIN occupations o ON o.id = os.occupation_id
                        JOIN course_aligned_occupations cao ON cao.occupation_id = o.id
                        WHERE
                            a.employment_status IN ('Employed', 'Self-employed') AND
                            a.ver_stat_dean = 'Verified' AND
                            c.archived = FALSE AND
                            c.code = ? AND
                            os.is_current = TRUE
                ) AS with_course_aligned_occupations,
                (
                    SELECT COUNT(DISTINCT a.id)
                    FROM alumni a
                    JOIN courses c ON c.id = a.course_id
                    WHERE 
                        c.archived = FALSE AND
                        c.code = ?
                ) AS with_course
            ";
            $statement = $this->pdo->prepare($withCourseAlignedOccupationsQuery);
            $statement->execute([$code, $code]);
            $raw = $statement->fetch();
            $courseAlignedOccupations[$code] = [
                'total_aligned_with_course' => $raw["with_course_aligned_occupations"],
                'total_with_course' => $raw["with_course"],
            ];
        }

        $formatted['occupation_course_alignement_info'] = $courseAlignedOccupations;
        
        return $formatted;
    }

    public function getAnalyticsForSchools()
    {
        $query = "
            SELECT
                (SELECT COUNT(*) FROM schools WHERE archived = TRUE) AS archived,
                (SELECT COUNT(*) FROM schools) AS total
        ";
        $raw = $this->pdo->query($query)->fetch();
        return [
            'archived' => $raw['archived'],
            'not_archived' => $raw['total'] - $raw['archived'],
            'total' => $raw['total'],
        ];
    }

    public function getAnalyticsForCourses()
    {
        $query = "
            SELECT
                (SELECT COUNT(*) FROM courses WHERE archived = TRUE) AS archived,
                (SELECT COUNT(*) FROM courses) AS total
        ";
        $raw = $this->pdo->query($query)->fetch();
        return [
            'archived' => $raw['archived'],
            'not_archived' => $raw['total'] - $raw['archived'],
            'total' => $raw['total'],
        ];
    }
    
    public function getAllAnalyticsForSysad($sysadUser): array
    {
        $deanAnalytics = $this->getUserAdminAnalytics(Role::DEAN);
        $pstaffAnalytics = $this->getUserAdminAnalytics(Role::PSTAFF);
        $companyAnalytics = $this->getUserCompanyAnalytics();
        $alumniAnalytics = $this->getUserAlumniAnalytics();
        $formatted = [
            'dean' => $deanAnalytics,
            'pstaff' => $pstaffAnalytics,
            'company' => $companyAnalytics,
            'alumni' => $alumniAnalytics,
            'schools' => $this->getAnalyticsForSchools(),
            'courses' => $this->getAnalyticsForCourses(),
        ];
        
        if ($sysadUser['default_sysad']) {
            $formatted['sysad'] = $this->getUserAdminAnalytics(Role::SYSAD);
        }
        
        return $formatted;
    }

    public function getAllAnalyticsForDean(): array
    {
        $alumniAnalytics = $this->getUserAlumniAnalytics();
        return [
            'alumni' => $alumniAnalytics,
            'courses' => $this->getAnalyticsForCourses(),
        ];
    }

    public function getAllAnalyticsForPstaff(): array
    {
        $companyAnalytics = $this->getUserCompanyAnalytics();
        return ['company' => $companyAnalytics];
    }

    public function getByRole($user)
    {
        switch ($user['role']) {
            case Role::SYSAD:
                return $this->getAllAnalyticsForSysad($user);
            case Role::DEAN:
                return $this->getAllAnalyticsForDean();
            case Role::PSTAFF:
                return $this->getAllAnalyticsForPstaff();
        }
    }
}