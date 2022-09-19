<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use App\Helpers\Helper;

class HomeController extends Controller
{
    public function index(){
        return view('index');
    }

    public function crawlLink(){
        include_once('simple_html_dom.php');

        for($i = 1; $i < 1091;$i++) {
            $html = file_get_html("https://truyenfull.vn/danh-sach/truyen-moi/trang-".$i);
            if($html) {
                $stories = $html->find('.truyen-title a');
                foreach($stories as $story) {
                    $link =  $story->href;
                    $slug = preg_replace('(https://truyenfull.vn/)','', $link );
                    $slug = preg_replace('(/)','', $slug );
                    echo $link . "</br>";
                    flush();
                    \DB::table('links')->insertOrIgnore([
                        'link' => $link,
                        'status' => 1,
                        'slug' => $slug,
                    ]);
                }
                echo "</br>";
                echo "--------------------</br>";
                echo "page" . $i . "</br>";
                echo "--------------------</br>";
                $html->clear();
                unset($html);
            }else{
                \DB::table('page_errors')->insert([
                    'page' => $i,
                ]);
            }
            sleep(0.5);
        }
    }

    public function linkError () {
        include_once('simple_html_dom.php');

        $pages = \DB::table('page_errors')->where('status', 0)->take(5)->get();
        foreach ($pages as $i) {
            $html = file_get_html("https://truyenfull.vn/danh-sach/truyen-moi/trang-".$i->page);
            if($html) {
                $stories = $html->find('.truyen-title a');
                foreach($stories as $story) {
                    $link =  $story->href;
                    $slug = preg_replace('(https://truyenfull.vn/)','', $link );
                    $slug = preg_replace('(/)','', $slug );
                    echo $link . "</br>";
                    flush();
                    \DB::table('links')->insertOrIgnore([
                        'link' => $link,
                        'status' => 1,
                        'slug' => $slug,
                    ]);
                }
                $pages = \DB::table('page_errors')->where('id',$i->id)->update([
                    'status' => 1,
                ]);
                echo "</br>";
                echo "--------------------</br>";
                echo "page" . $i->page . "</br>";
                echo "--------------------</br>";
                $html->clear();
                unset($html);
            }
        }
        sleep(0.5);
        return back();
    }

    public function detail(){
        include_once('simple_html_dom.php');

        $links = \DB::table('links')->where('status', 1)->take(5)->get();
        foreach($links as $link) {
            $slug = $link->slug;
            $html = file_get_html("https://truyenfull.vn/". $slug);
            if($html) {
                $check_slug = \DB::table('stories')->where('slug',$slug)->first();
                if(!$check_slug) {
                    //name
                    $name = $html->find('h3.title', 0)->innertext;

                    //image
                    $image = $html->find('.books img', 0)->src;
                    Helper::curl_image($image, 'image-avatars/' . basename($image));

                    //full
                    $full_text = $html->find('.info div',3)->find('span',0)->innertext;
                    if ($full_text == "Full"){
                        $full = 1;
                    }else {
                        $full = 0;
                    }

                    //author
                    $author_slug = $html->find('[itemprop="author"]', 0)->href;
                    $author_slug = preg_replace('(https://truyenfull.vn/tac-gia/)', '', $author_slug);
                    $author_slug = preg_replace('(/)', '', $author_slug);
                    $author = \DB::table('authors')->where('slug',$author_slug)->first();
                    if($author){
                        $author_id = $author->id;
                    }else{
                        $author_name = $html->find('[itemprop="author"]', 0)->plaintext;
                        \DB::table('authors')->insert([
                            'name' => $author_name,
                            'slug' => $author_slug,
                        ]);
                        $author_new = \DB::table('authors')->where('slug',$author_slug)->first();
                        $author_id = $author_new->id;
                    }


                    //rate
                    if($html->find('.rate .small', 0)->plaintext === 'Chưa có đánh giá nào, bạn hãy là người đầu tiên đánh giá truyện này!'){
                        $rate = 0;
                        $number_of_votes = 0;
                    }else{
                        $rate = $html->find('[itemprop="ratingValue"]', 0)->plaintext;
                        $number_of_votes = $html->find('[itemprop="ratingCount"]', 0)->plaintext;
                    }

                    //des
                    $content = $html->find('.desc-text', 0)->innertext;
                    $content = preg_replace('(<div.*?class=.*?>.*?</div>)','', $content);
                    $content = preg_replace('(<a.*?class=.*?>.*?</a>)','', $content);

                    // dd($content,$slug,$name,$author_id,$rate,$number_of_votes);

                    //add
                    $total_chapter = 0;
                    if($html->find('.list-chapter li',0)){
                        $link = $html->find('.list-chapter li',0)->find('a',0)->href ;
                        $link_chapter_first = preg_replace('(https://truyenfull.vn/' . $slug .  ')','', $link);
                        $link_chapter_first = preg_replace('(/)','', $link_chapter_first);
                        \DB::table('stories')->insertOrIgnore([
                            'name' => $name,
                            'image' => basename($image),
                            'slug' => $slug,
                            'rate' => $rate,
                            'number_of_votes' => $number_of_votes,
                            'author_id' => $author_id,
                            'full' => $full,
                            'content' => $content,
                            'type' => 1,
                            'url_first_chapter' => $link_chapter_first,
                        ]);
                        $story = \DB::table('stories')->where('slug',$slug)->first();
                        $types = $html->find('.info [itemprop="genre"]');
                        foreach($types as $type){
                            $name_type = $type->plaintext;
                            $type = \DB::table('types')->where('name',$name_type)->first();
                            \DB::table('type_story')->insertOrIgnore([
                                'type_id' => $type->id,
                                'story_id' => $story->id,
                            ]);
                        }
                    }
                }
            }else{
            }
            $html->clear();
            unset($html);
        }
    }
}
