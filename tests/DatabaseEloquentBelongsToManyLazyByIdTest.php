<?php

namespace Fluent\Orm\Tests;

use CodeIgniter\Database\Config;
use Fluent\Orm\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyLazyByIdTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema()->addField([
            'id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'email'=> ['type' => 'varchar', 'constraint' => 255, 'unique' => true],
        ])
        ->addPrimaryKey('id')
        ->createTable('users', true);

        $this->schema()->addField([
            'aid' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'=> ['type' => 'varchar', 'constraint' => 255,],
        ])
        ->addPrimaryKey('aid')
        ->createTable('articles', true);

        $this->schema()->addField([
            'article_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
        ])
        ->addKey(['article_id', 'user_id'])
        ->addForeignKey('article_id', 'articles', 'aid')
        ->addForeignKey('user_id', 'users', 'id')
        ->createTable('article_user', true);
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->dropTable('users', true);
        $this->schema()->dropTable('articles', true);
        $this->schema()->dropTable('article_user', true);
    }

    public function testBelongsToLazyById()
    {
        $this->seedData();

        $user = BelongsToManyLazyByIdTestTestUser::query()->first();
        $i = 0;

        $user->articles()->lazyById(1)->each(function ($model) use (&$i) {
            $i++;
            $this->assertEquals($i, $model->aid);
        });

        $this->assertSame(3, $i);
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        $user = BelongsToManyLazyByIdTestTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        BelongsToManyLazyByIdTestTestArticle::query()->insertBatch([
            ['aid' => 1, 'title' => 'Another title'],
            ['aid' => 2, 'title' => 'Another title'],
            ['aid' => 3, 'title' => 'Another title'],
        ]);

        $user->articles()->sync([3, 1, 2]);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \CodeIgniter\Database\Forge
     */
    protected function schema()
    {
        return Config::forge();
    }
}

class BelongsToManyLazyByIdTestTestUser extends Model
{
    protected $table = 'users';
    protected $fillable = ['id', 'email'];
    public $timestamps = false;

    public function articles()
    {
        return $this->belongsToMany(BelongsToManyLazyByIdTestTestArticle::class, 'article_user', 'user_id', 'article_id');
    }
}

class BelongsToManyLazyByIdTestTestArticle extends Model
{
    protected $primaryKey = 'aid';
    protected $table = 'articles';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['aid', 'title'];
}