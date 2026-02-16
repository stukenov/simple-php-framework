<?php

namespace app\controller;

use support\Request;
use app\model\Post;

class BlogController
{
    /**
     * Главная страница блога - список всех постов
     */
    public function index(Request $request)
    {
        $posts = Post::all();
        return view('blog/index', ['posts' => $posts]);
    }

    /**
     * Форма создания нового поста
     */
    public function create(Request $request)
    {
        return view('blog/create');
    }

    /**
     * Сохранение нового поста
     */
    public function store(Request $request)
    {
        $data = $request->post();

        $post = new Post();
        $post->title = $data['title'] ?? '';
        $post->content = $data['content'] ?? '';
        $post->author = $data['author'] ?? '';
        $post->email = $data['email'] ?? '';
        $post->category = $data['category'] ?? 'general';
        $post->save();

        return redirect('/blog');
    }

    /**
     * Просмотр отдельного поста
     */
    public function show(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response('Пост не найден', 404);
        }

        return view('blog/show', ['post' => $post]);
    }
}
