<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

final class Db
{
    private static ?array $config = null;
    private static ?PDO $pdo = null;

    public static function init(array $cfg): void
    {
        self::$config = $cfg;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            if (self::$config === null) {
                throw new \RuntimeException('Db not initialized — call Db::init() first');
            }
            $cfg = self::$config;
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'], $cfg['port'], $cfg['name'], $cfg['charset']
            );
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $cfg['options']);
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $place = array_map(static fn(string $c): string => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`,`', $cols),
            implode(',', $place)
        );
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $params = [];
        foreach ($data as $k => $v) {
            $sets[] = "`$k` = :set_$k";
            $params["set_$k"] = $v;
        }
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $sets), $where);
        return self::query($sql, $params + $whereParams)->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
