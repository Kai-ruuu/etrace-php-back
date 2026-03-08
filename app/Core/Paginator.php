<?php

class Paginator
{
    public function __construct($pdo, $table, $page, $perPage)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->page = max(1, (int)$page);
        $this->perPage = min(100, max(1, (int)$perPage));
        $this->offset = ($this->page - 1) * $this->perPage;
    }

    public function run($query, $where, $whereBindings, $formatter = null, $countQuery = null)
    {
        $finalCountQuery = $countQuery
            ? "{$countQuery} {$where}"
            : "SELECT COUNT(*) FROM ({$query} {$where}) AS count_query";

        $countStatement = $this->pdo->prepare($finalCountQuery);
        $countStatement->execute($whereBindings);
        $total = $countStatement->fetchColumn();

        $statement = $this->pdo->prepare($query . " {$where} LIMIT ? OFFSET ?");
        $statement->execute([...$whereBindings, $this->perPage, $this->offset]);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $finalResults = $results;

        if ($formatter) {
            $finalResults = [];
            $needsVacancyGrouping = !empty($results) && isset($results[0]["vid"]);
            $needsJobPostGrouping = !empty($results) && isset($results[0]["jpcid"]);

            if ($needsVacancyGrouping) {
                $grouped = [];

                foreach ($results as $result) {
                    $uid = $result["uid"];
                    if (!isset($grouped[$uid])) {
                        $grouped[$uid] = $result;
                        $grouped[$uid]["vacancies"] = [];
                    }
                    if (!empty($result["vid"])) {
                        $grouped[$uid]["vacancies"][] = [
                            "id"             => $result["vid"],
                            "job_title"      => $result["vjob_title"],
                            "slots"          => $result["vslots"],
                            "qualifications" => json_decode($result["vqualifications"], true),
                        ];
                    }
                }

                foreach ($grouped as $result) {
                    $finalResults[] = call_user_func($formatter, $result);
                }
            } else if ($needsJobPostGrouping) {
                $grouped = [];

                foreach ($results as $result) {
                    $jpid = $result["jpid"];

                    if (!isset($grouped[$jpid])) {
                        $grouped[$jpid] = $result;
                        $grouped[$jpid]["target_courses"] = [];
                    }
                    if (!empty($result["jpcid"])) {
                        $grouped[$jpid]["target_courses"][] = [
                            "id"          => $result["jpcid"],
                            "job_post_id" => $result["jpcjob_post_id"],
                            "course_id"   => $result["jpccourse_id"],
                            "course_name" => $result["cname"],
                        ];
                    }
                }

                foreach ($grouped as $result) {
                    $finalResults[] = call_user_func($formatter, $result);
                }
            } else {
                foreach ($results as $result) {
                    $finalResults[] = call_user_func($formatter, $result);
                }
            }
        }

        return [
            'data' => $finalResults,
            'total' => $total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => ceil($total / $this->perPage),
            'has_next' => $this->page < ceil($total / $this->perPage),
            'has_prev' => $this->page > 1,
        ];
    }
}