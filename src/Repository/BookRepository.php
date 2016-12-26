<?php
namespace src\Repository;

use \Doctrine\DBAL\DBALException;

class BookRepository
{
    /**
    * @var - resourse $connection - stores connection to DB
    */
    protected $connection;
    /**
    * @param - resourse $connection
    */
    function __construct($connection)
    {
        $this->connection = $connection;
    }
    /**
    * Get books by category
    *
    * @param int $IdCategory - category for selection
    * @param int $firstPos - position for start selection
    * @param int $limit - limit of selection
    *
    * @return array $books
    */
    public function getBooksByCat(int $IdCategory, int $firstPos = 0, int $limit = 10): array
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $query = $queryBuilder
                ->select('b.book_id', 'b.book_name', 'b.description', 'b.price')
                ->from('books', 'b')
                ->innerJoin('b', 'categories_has_books', 'c_h_b', 'b.book_id = c_h_b.books_book_id')
                ->innerJoin('c_h_b', 'categories', 'c', 'c.category_id = c_h_b.categories_category_id')
                ->innerJoin('c' , 'categories', 'p', 'p.category_id = ?')
                ->where('c.left_key >= p.left_key')
                ->andWhere('c.right_key <= p.right_key')
                ->setFirstResult($firstPos)
                ->setMaxResults($limit);
            //echo $query;
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(1, $IdCategory);
            $stmt->execute();
            $books = $stmt->fetchAll();
            return $books;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Get all category
    *
    * @return array $categories
    */
    public function getCategories()
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $query = $queryBuilder
                ->select('category_id', 'name_category', 'row')
                ->from('categories')
                ->addOrderBy('left_key');
            $stmt = $this->connection->query($query);
            $categories = $stmt->fetchAll();
            return $categories;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Add book to DB
    *
    * @param string $bookName
    * @param string $bookDes
    * @param string $bookPrice
    *
    * @return boolean - successeful/failed
    */
    public function saveBook(string $bookName, string $bookDes, string $bookPrice)
    {
        try
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $query = $queryBuilder
                ->insert('books')
                ->values(
                    [
                        'book_name' => '?',
                        'description' => '?',
                        'price' => '?'
                    ]
                );
                $stmt = $this->connection->prepare($query);
                $stmt->bindValue(1, $bookName);
                $stmt->bindValue(2, $bookDes);
                $stmt->bindValue(3, $bookPrice);
                if($stmt->execute())
                    echo "SUCCESSEFULL";
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Add category to categories
    *
    * Stored procedure is using here because query is complicated because architecture of categories table implements nested sets 
    *
    * @param $idParentCategory - any category must have parent category, except main category
    * @param $nameNewCategory - name of new category, that will be add
    *
    * @retun boolean - procedure success
    */
    public function addCategory(int $idParentCategory, string $nameNewCategory)
    {
        try
        {
            $query = 'call add_category(?, ?)';
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(1, $idParentCategory);
            $stmt->bindValue(2, $nameNewCategory);
            $stmt->execute();
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /**
    * Delete category from categories
    *
    * Stored procedure is using here because query is complicated because architecture of categories table implements nested sets 
    *
    * @param $idDeleteCategory - any category must have parent category, except main category
    *
    * @retun boolean - procedure success
    */
    public function deleteCategory(int $idDeleteCategory)
    {
        try
        {
            $query = 'call delete_category(' . $idDeleteCategory . ')';
            $this->connection->query($query);
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
    /** 
    * Move category
    *
    * Move category used in case when need change parent category
    * 
    * @param integer $idMovedCategories - moved categories
    * @param integer $idNewParentCategory - new parent category
    *
    * @retun boolean - procedure success
    */
    public function moveCategory(int $idMovedCategories, int $idNewParentCategory)
    {
        try
        {
            $query = 'call change_parent(' . $idMovedCategories . ', ' . $idNewParentCategory . ')';
            $this->connection->query($query);
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
     /** 
    * Change order of subcategories from one category
    * 
    * @param integer $idMovedCategories - moved categories
    * @param integer $idSetAfterCategories - category after that moving category will stay, if it is id parent category then category will stay at the first  place
    *
    * @retun boolean - procedure success
    */
    public function changeOrder(int $idMovedCategories, int $idSetAfterCategories)
    {
        try
        {
            $query = 'call change_order(' . $idMovedCategories . ', ' . $idSetAfterCategories . ')';
            $this->connection->query($query);
            return true;
        }catch(DBALException $exception)
        {
            throw $exception;
        }
    }
}
?>
