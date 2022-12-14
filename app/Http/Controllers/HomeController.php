<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use App\Models\Story;
use App\Models\Type;
use App\Models\TypeStory;
use App\Models\Author;
use App\Models\ListTruyenFull;
use App\Helpers\Helper;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index(){
        return view('index');
    }



    public function crawlLink(){
        include_once('simple_html_dom.php');

        for($i = 1; $i < 1100;$i++) {
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
            sleep(1);
        }
    }

    public function linkError () {
        include_once('simple_html_dom.php');

        $pages = \App\Models\PageError::where('status', 0)->take(5)->get();
        foreach ($pages as $i) {
            $i->status = -1;
            $i->save();
            $html = file_get_html("https://truyenfull.vn/danh-sach/truyen-moi/trang-".$i->page);
            if($html) {
                $stories = $html->find('.truyen-title a');
                foreach($stories as $story) {
                    $link =  $story->href;
                    $slug = preg_replace('(https://truyenfull.vn/)','', $link );
                    $slug = preg_replace('(/)','', $slug );

                    \DB::table('links')->insertOrIgnore([
                        'link' => $link,
                        'slug' => $slug,
                    ]);
                }
                $pages = \App\Models\PageError::where('id',$i->id)->update([
                    'status' => 1,
                ]);
                $html->clear();
                unset($html);
                echo '<span style="color:green"><b>'.$i->page.'...Done...</b></span><br />';
            }else{
                echo '<span style="color:red"><b>'.$i->page.'...Die...</b></span><br />';
            }
            sleep(1);
        }
        header("refresh: 1");
    }

    public function detail(){
        include_once('simple_html_dom.php');

        $links = Link::where('exist', 0)->take(10)->get();
        foreach($links as $link) {
            $slug = $link->slug;
            $html = file_get_html("https://truyenfull.vn/". $slug);
            if($html) {
                $check404 = $html->find('a',0)->title;
                if($check404 == "Tr??? v??? trang ch???") {
                    \DB::table('links')->where('slug',$slug)->update([
                        'exist' => 2,
                    ]);
                }else{
                    $check_slug = Story::where('slug',$slug)->first();
                    if(!$check_slug) {
                        //name
                        $name = $html->find('h3.title', 0)->plaintext;

                        //image
                        $image = $html->find('.books img', 0)->src;
                        Helper::curl_image($image, 'image-avatars/' . basename($image));

                        //full
                        $full_div = $html->find('.info div',3);
                        if($full_div) {
                            $full_text = $full_div->find('span',0)->plaintext;
                            if ($full_text == "Full" && $full_text){
                                $full = 1;
                            }else {
                                $full = 0;
                            }
                        }else {
                            $full_div = $html->find('.info div',3);
                            if($full_div) {
                                $full_text = $full_div->find('span',0)->plaintext;
                                if ($full_text == "Full" && $full_text){
                                    $full = 1;
                                }else {
                                    $full = 0;
                                }
                            }else {
                                $full = 0;
                            }
                        }



                        //author
                        $author_slug = $html->find('[itemprop="author"]', 0)->href;
                        $author_slug = preg_replace('(https://truyenfull.vn/tac-gia/)', '', $author_slug);
                        $author_slug = preg_replace('(/)', '', $author_slug);
                        $author = Author::where('slug',$author_slug)->first();
                        if($author){
                            $author_id = $author->id;
                        }else{
                            $author_name = $html->find('[itemprop="author"]', 0)->plaintext;
                            $author = new Author;
                            $author->name = $author_name;
                            $author->slug = $author_slug;
                            $author->save();
                            $author_id = $author->id;
                        }


                        //rate
                        if($html->find('.rate .small', 0)->plaintext === 'Ch??a c?? ????nh gi?? n??o, b???n h??y l?? ng?????i ?????u ti??n ????nh gi?? truy???n n??y!'){
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
                                'url_first_chapter' => $link_chapter_first,
                            ]);
                            $story = Story::where('slug',$slug)->first();
                            $types = $html->find('.info [itemprop="genre"]');
                            foreach($types as $type){
                                $name_type = $type->plaintext;
                                $type = Type::where('name',$name_type)->first();
                                \DB::table('type_story')->insertOrIgnore([
                                    'type_id' => $type->id,
                                    'story_id' => $story->id,
                                ]);
                            }
                        }
                        \DB::table('links')->where('slug',$slug)->update([
                            'status' => 1,
                            'exist' => 1,
                        ]);
                    }
                }
                $html->clear();
                unset($html);
            }else{
                \DB::table('links')->where('slug',$slug)->update([
                    'exist' => 2,
                ]);
                echo $link->id . "</br>";
            }
        }
        sleep(0.5);
        return back();
    }

    public function chap() {
        include_once('simple_html_dom.php');

        $story = \DB::table('stories')->where('status', 0)->first();
        for($i = 1;$i < 200; $i++) {
            $html = file_get_html("https://truyenfull.vn/" .$story->slug. "/trang-" .$i);
            if($html) {
                $uls = $html->find('ul.list-chapter');
                if($uls) {
                    foreach ($uls as $ul) {
                        $lis = $ul->find('li');
                        if($lis) {
                            foreach ($lis as $li) {
                                $link = $li->find('a',0)->href;
                                $slug = preg_replace('(https://truyenfull.vn/' . $story->slug .  ')','', $link );
                                $slug = preg_replace('(/)','', $slug );
                                $check = \DB::table('chapter')->where('story_id',$story->id)->where('slug',$slug)->first();
                                if($check) {
                                    \DB::table('stories')->where('id', $story->id)->update([
                                        'status' => 1,
                                    ]);
                                    $html->clear();
                                    unset($html);
                                    break;
                                }else {
                                    \DB::table('chapter')->insertOrIgnore([
                                        'slug' => $slug,
                                        'story_id' => $story->id,
                                    ]);
                                }
                            }
                        }
                    }
                }
                $html->clear();
                unset($html);
            }else {
                if($i = 1) {
                    \DB::table('stories')->where('id', $story->id)->update([
                        'status' => 2,
                    ]);
                }else {
                    \DB::table('stories')->where('id', $story->id)->update([
                        'status' => 1,
                    ]);
                }
                break;
            }
        }
        sleep(0.5);
        return back();
    }

    public function exist() {
        $stories = ListTruyenFull::select('slug')->get();
        foreach ($stories as $story){
            $exist = Link::where('exist',0)->where('slug', $story->slug)->first();
            if($exist) {
                $exist->exist = 1;
                $exist->save();
            }else {
                echo $story->slug . "</br>";
            }
        }
    }

     public function resetStatus(){
        $items = \DB::table('stories')->where('status', 1)->get();
        foreach ($items as $item) {
            \DB::table('stories')->where('id', $item->id)->update([
                'status' => 0,
            ]);
        }
    }
}
