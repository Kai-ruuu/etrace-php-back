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

    public function run($query, $where, $whereBindings, $formatter = null)
    {
        $countStatement = $this->pdo->prepare("SELECT COUNT(*) FROM ({$query} {$where}) AS count_query");
        $countStatement->execute($whereBindings);
        $total = $countStatement->fetchColumn();

        $statement = $this->pdo->prepare($query . " {$where} LIMIT ? OFFSET ?");
        $statement->execute([...$whereBindings, $this->perPage, $this->offset]);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $finalResults = $results;

        if ($formatter) {
            $finalResults = [];

            foreach ($results as $result) {
                $finalResults[] = call_user_func($formatter, $result);
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