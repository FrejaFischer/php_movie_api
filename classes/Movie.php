<?php

require_once __DIR__ . '/DB.php';

class Movie extends DB
{
    const DB_CONN_ERROR = 'Database connection unsuccessful';
    const DB_SQL_ERROR = 'Database query unsuccessful';
    const NO_ROWS = 'No rows affected';
    
    /**
     * Returns a formatted error message
     * 
     * @param string The message associated with the error
     * @param int the reponsecode to send back
     * @return array an associative array with the corresponding error message
     */
    private function setError(string $errorMessage, int $responseCode): array
    {
        http_response_code($responseCode);
        $errorInfo['_error'] = $errorMessage;
        Logger::logText('error', $errorInfo);
        return $errorInfo;
    }

    /**
     * Add HATEOAS to the returned results
     */
    public function addHATEOAS()
    {
        $serverPath = $_SERVER['REQUEST_URI'];
        $hateoas = [['rel'=>'movies', 'href'=>$serverPath, 'type'=>'GET'], 
        ['rel'=>'movies', 'href'=>$serverPath . '/{id}', 'type'=>'GET'], 
        ['rel'=>'movies', 'href'=>$serverPath, 'type'=>'POST'], 
        ['rel'=>'movies', 'href'=>$serverPath . '/{id}', 'type'=>'PUT'], 
        ['rel'=>'movies', 'href'=>$serverPath . '/{id}', 'type'=>'DELETE']];
        
        return $hateoas;
    }

    /**
     * Retrieves information of all the movies
     * 
     * @return array the total number of movies and all movie fields (ID, movie name) for each movie ordered by movie name
     */
    public function list(): array
    {
        if (!$this->connect()) { return Movie::setError(Movie::DB_CONN_ERROR, 500); };
        $results = [];

        $sql =<<<'SQL'
            SELECT nMovieID AS id, cName AS name 
            FROM movies 
            ORDER BY cName;
        SQL;

        try {
            $stmt = $this->pdo->query($sql);                
            $results['_total'] = $stmt->rowCount();
            
            $movies = $stmt->fetchAll();
            $results['data'] = $movies;
        } catch (PDOException $e) {
            $results = false;
        } finally {
            $stmt = null;
            $this->disconnect();
        }

        if($results) {
            $results['_links'] = $this->addHATEOAS();
        }
        
        return !$results ? Movie::setError(Movie::DB_SQL_ERROR, 500) : $results;
    }

    /**
     * Retrieves the movies whose name matches a certain text
     * 
     * @param string text upon which to execute the search
     * @return array the total number of movies matching the search plus matching movie fields (ID, movie name) for each movie
     *          ordered by movie name
     */
    public function search(string $searchText): array 
    {
        if (!$this->connect()) { return Movie::setError(Movie::DB_CONN_ERROR, 500); };
        $results = [];

        $sql =<<<'SQL'
            SELECT nMovieID AS id, cName AS name
            FROM movies
            WHERE cName LIKE ?
            ORDER BY cName;
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['%' . $searchText . '%']);

            $results['_total'] = $stmt->rowCount();            
            $results['data'] = $stmt->fetchAll();
        } catch (PDOException $e) {
            $results = false;
        } finally {
            $stmt = null;
            $this->disconnect();
        }

        if($results) {
            $results['_links'] = $this->addHATEOAS();
        }
        
        return !$results ? Movie::setError(Movie::DB_SQL_ERROR, 500) : $results;
    }

    /**
     * Retrieves the ID and name of a movie based on its ID
     * 
     * @param int ID of the movie to find
     * @return array number of movies returned (0 or 1) and ID and name of the matching movie, if it exists
     */
    public function get(int $movieID): array
    {
        if (!$this->connect()) { return Movie::setError(Movie::DB_CONN_ERROR, 500); };
        $results = [];
        
        $sql =<<<'SQL'
            SELECT nMovieID AS id, cName AS name
            FROM movies 
            WHERE nMovieID = ?;
        SQL;

        try {            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$movieID]);

            $results['_total'] = $stmt->rowCount();
            $data = $stmt->fetch();
            $results['data'] = !$data ? [] : $data;
        }  catch (PDOException $e) {
            $results = false;
        } finally {
            $stmt = null;
            $this->disconnect();
        }

        if($results) {
            $results['_links'] = $this->addHATEOAS();
        }
        
        return !$results ? Movie::setError(Movie::DB_SQL_ERROR, 500) : $results;
    }

    /**
     * Inserts a new movie
     * 
     * @param string name of the new movie
     * @return array an associative array with the new movie ID
     *      or with error information if there was an error
     */
    public function add(string $movieName): array 
    {
        if (!$this->connect()) { return Movie::setError(Movie::DB_CONN_ERROR, 500); };
        $sql =<<<'SQL'
            INSERT INTO movies
                (cName)
            VALUES (?);
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$movieName]);
            $rowCount = $stmt->rowCount();
            $lastInsertID = $this->pdo->lastInsertId();
            $results = true;
        } catch (PDOException $e) {
            $results = false;
        } finally {
            $stmt = null;
            $this->disconnect();
        }
        
        if (!$results) {
            return Movie::setError(Movie::DB_SQL_ERROR, 500);
        }

        return $rowCount === 0 ? Movie::setError(Movie::NO_ROWS, 200) : ['id' => $lastInsertID, '_links' => $this->addHATEOAS()];
    }

    /**
     * Updates the name of a movie
     * 
     * @param int id of the movie to update
     * @param string new name of the movie
     * @return array an associative array with the updated movie ID
     *      or with error information if there was an error
     */
    public function update(int $movieID, string $movieName): array 
    {
        if (!$this->connect()) { return Movie::setError(Movie::DB_CONN_ERROR, 500); };
        $sql =<<<'SQL'
            UPDATE movies
            SET cName = ?
            WHERE nMovieID = ?;
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$movieName, $movieID]);  
            $rowCount = $stmt->rowCount();
            $results = true;
        } catch (PDOException $e) {
            $results = false;
        } finally {
            $stmt = null;
            $this->disconnect();
        }

        if (!$results) {
            return Movie::setError(Movie::DB_SQL_ERROR, 500);
        }

        return $rowCount === 0 ? Movie::setError(Movie::NO_ROWS, 200) : ['id' => $movieID, '_links' => $this->addHATEOAS()];
    }

    /**
     * Deletes a movie
     * 
     * @param int id of the movie to delete
     * @return array an associative array with the deleted movie ID
     *      or with error information if there was an error
     */
    public function delete(int $movieID): array 
    {
        if (!$this->connect()) { return Movie::setError(Movie::DB_CONN_ERROR, 500); };
        $sql =<<<'SQL'
            DELETE FROM movies
            WHERE nMovieID = ?;
        SQL;
               
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$movieID]);
            $rowCount = $stmt->rowCount();
            $results = true;
        } catch (PDOException $e) {
            $results = false;
        } finally {
            $stmt = null;
            $this->disconnect();
        }
        
        if (!$results) {
            return Movie::setError(Movie::DB_SQL_ERROR, 500);
        }

        return $rowCount === 0 ? Movie::setError(Movie::NO_ROWS, 200) : ['id' => $movieID, '_links' => $this->addHATEOAS()];
    }
}