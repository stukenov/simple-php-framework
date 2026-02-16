<?php

namespace app\model;

class Post
{
    public $id;
    public $title;
    public $content;
    public $author;
    public $email;
    public $category;
    public $created_at;
    public $updated_at;

    private static function getDb()
    {
        return new \PDO('sqlite:' . base_path() . '/database.sqlite');
    }

    /**
     * Получить все посты, отсортированные по дате создания
     */
    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query('SELECT * FROM posts ORDER BY created_at DESC');
        $posts = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $post = new self();
            foreach ($row as $key => $value) {
                $post->$key = $value;
            }
            $posts[] = $post;
        }

        return $posts;
    }

    /**
     * Найти пост по ID
     */
    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $post = new self();
            foreach ($row as $key => $value) {
                $post->$key = $value;
            }
            return $post;
        }

        return null;
    }

    /**
     * Сохранить пост
     */
    public function save()
    {
        $db = self::getDb();

        if ($this->id) {
            // Обновление существующего поста
            $stmt = $db->prepare('UPDATE posts SET title = ?, content = ?, author = ?, email = ?, category = ?, updated_at = datetime("now") WHERE id = ?');
            $stmt->execute([$this->title, $this->content, $this->author, $this->email, $this->category, $this->id]);
        } else {
            // Создание нового поста
            $stmt = $db->prepare('INSERT INTO posts (title, content, author, email, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
            $stmt->execute([$this->title, $this->content, $this->author, $this->email, $this->category]);
            $this->id = $db->lastInsertId();
        }
    }
}
