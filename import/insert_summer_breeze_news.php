<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
$pdo=getDB();
$file=__DIR__.'/summer_breeze_articles_data.json';
if(!is_file($file)){fwrite(STDERR,"Datendatei fehlt.\n");exit(1);}
$rows=json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
$pdo->beginTransaction();
try{
 $pdo->exec("UPDATE categories SET name='Summer Breeze', parent_id=54, description='Offizielle Meldungen des Summer Breeze Open Air' WHERE id=52");
 $cat=(int)$pdo->query("SELECT id FROM categories WHERE slug='sb' LIMIT 1")->fetchColumn();
 if(!$cat)throw new RuntimeException('Kategorie sb fehlt');
 $sql="INSERT INTO articles (title,slug,content,excerpt,category_id,author,featured_image,status,created_at,updated_at) VALUES (:title,:slug,:content,:excerpt,:category_id,'Redaktion',:image,'published',:created_at,NOW()) ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content),excerpt=VALUES(excerpt),category_id=VALUES(category_id),featured_image=VALUES(featured_image),status='published',created_at=VALUES(created_at),updated_at=NOW()";
 $st=$pdo->prepare($sql);
 foreach($rows as $x)$st->execute([':title'=>$x['title'],':slug'=>$x['slug'],':content'=>$x['content'],':excerpt'=>$x['excerpt'],':category_id'=>$cat,':image'=>$x['image'],':created_at'=>$x['date'].' 12:00:00']);
 $pdo->commit();echo "Kategorie {$cat}; ".count($rows)." offizielle Summer-Breeze-Meldungen veröffentlicht.\n";
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,$e->getMessage()."\n");exit(1);}
