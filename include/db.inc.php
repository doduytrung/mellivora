<?php

// always connect to database
$db = new PDO(DB_ENGINE.':host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASSWORD);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

function db_insert ($table, array $fields) {
    global $db;

    try {
        $sql = 'INSERT INTO '.$table.' (';
        $sql .= implode(', ', array_keys($fields));
        $sql .= ') VALUES (';
        $sql .= implode(', ', array_fill(0, count($fields), '?'));
        $sql .= ')';

        $stmt = $db->prepare($sql);

        // fix null values
        array_walk($fields, 'null_to_bool');

        // get the field values
        $values = array_values($fields);

        $stmt->execute($values);

        return $db->lastInsertId();

    } catch (PDOException $e) {
        sql_exception($e);
    }
}

function db_update ($table, array $fields, array $where, $whereGlue = 'AND') {
    global $db;

    try {
        $sql = 'UPDATE '.$table.' SET ';
        $sql .= implode('=?, ', array_keys($fields)).'=? ';
        $sql .= 'WHERE '.implode('=? '.$whereGlue.' ', array_keys($where)).'=?';

        $stmt = $db->prepare($sql);

        // fix null values
        array_walk($fields, 'null_to_bool');

        // get the field values and "WHERE" values. merge them into one array.
        $values = array_merge(array_values($fields), array_values($where));

        // execute the statement
        $stmt->execute($values);

        return $stmt->rowCount();

    } catch (PDOException $e) {
        sql_exception($e);
    }
}

function db_delete ($table, array $where, $whereGlue = 'AND') {
    global $db;

    try {
        $sql = 'DELETE FROM '.$table.' ';
        $sql .= 'WHERE '.implode('=? '.$whereGlue.' ', array_keys($where)).'=?';

        $stmt = $db->prepare($sql);

        // get the field values and "WHERE" values
        $values = array_values($where);

        // execute the statement
        $stmt->execute($values);

        return $stmt->rowCount();

    } catch (PDOException $e) {
        sql_exception($e);
    }
}

function db_select ($table, array $fields, array $where = null, $all = true, $whereGlue = 'AND') {
    global $db;

    try {
        $sql = 'SELECT '.implode(', ', $fields).' ';
        $sql .= 'FROM '.$table.' ';

        if ($where) {
            $sql .= 'WHERE '.implode('=? '.$whereGlue.' ', array_keys($where)).'=?';
        }

        $stmt = $db->prepare($sql);

        // get the field values and "WHERE" values. merge them into one array.
        $values = array_values($where);

        // execute the statement
        $stmt->execute($values);

        if ($all) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        else {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        sql_exception($e);
    }
}

function db_query ($query, array $values = null, $all = true) {
    global $db;

    try {
        if ($values) {
            $stmt = $db->prepare($query);
            $stmt->execute($values);
        }

        else {
            $stmt = $db->query($query);
        }

        if ($all) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        else {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        sql_exception($e);
    }
}

function sql_exception (PDOException $e) {
    log_exception($e);
    message_error('An SQL exception occurred. Please check the exceptions log.');
}

function null_to_bool(&$val, $key) {
    if (!isset($val)) {
        $val = 0;
    }
}