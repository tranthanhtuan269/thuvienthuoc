<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use App\Models\Thuoc;
use App\Models\Thanhphan;
use App\Models\Thuonghieu;
use App\Models\Danhmuc;
use App\Models\Dangbaoche;
use App\Models\Nhasanxuat;
use App\Models\Noisanxuat;
use App\Models\Quycach;
use App\Models\Thuoccanketoa;
use App\Models\Xuatxu;
use App\Helpers\Helper;

class HomeController extends Controller
{
    public function index(){
        return view('index');
    }

    public function crawl(){
        include_once('simple_html_dom.php');

        $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

        foreach($alphabet as $key){
            $count = 1;

            while(1){
                $html = file_get_html("https://nhathuoclongchau.com/thuoc/tra-cuu-thuoc-a-z?alphabet=$key&page=$count");

                $arr = [];
                $a_list = $html->find('.drug-row a');
                if(count($a_list) == 0) break;

                foreach($a_list as $e){
                    $arr[] = ['link' => $e->href];
                }
                
                \DB::table('links')->insertOrIgnore($arr);

                // clean up memory
                $html->clear();
                unset($html);
                $count++;
            }
        }
    }

    public function crawl2(){
        include_once('simple_html_dom.php');

        $links = Link::where('status', 0)->take(100)->get();

        foreach($links as $link){
            $link->status = -1;
            $link->save();
            $html = file_get_html('https://nhathuoclongchau.com' . $link->link);

            $thuoc = new Thuoc;
            $thuoc->link = $link->link;
            $thuoc->status = -1;
            $thuoc->save(); // save to have id
            
            $motasp = $html->find('#motasanpham',0);
            if($motasp){
                $thuoc->slug = substr($html->find('#motasanpham',0)->innertext, 0, -10);
                $thuoc->ten = substr($html->find('#motasanpham',0)->innertext, 0, -10);
            }

            $attrs = $html->find('.attr-product tr');
            
            foreach($attrs as $attr){

                if (str_contains($attr->innertext, 'Danh mục:')) { 
                    // Danh mục:
                    $danh_muc = $attr->find('a',0);
                    $danh_muc_list = explode('/', $danh_muc->href);

                    $danhmuc = Danhmuc::where('ten', $attr->find('a',0)->innertext)->first();

                    if(!isset($danhmuc)){
                        $danhmuc = new Danhmuc;
                        $danhmuc->slug = last($danh_muc_list);
                        $danhmuc->ten = $danh_muc->innertext;
                        $danhmuc->save();
                    }
                    $thuoc->danhmuc_id = $danhmuc->id;
                }else if (str_contains($attr->innertext, 'Thành phần chính:')) { 
                    // Thành phần chính:
                    $thanh_phan_chinhs = $attr->find('a');
                    foreach($thanh_phan_chinhs as $thanh_phan_chinh){
                        $thanh_phan_chinh_list = explode('/', $thanh_phan_chinh->href);

                        $thanhphanchinh = Thanhphan::where('ten', $thanh_phan_chinh->innertext)->first();

                        if(!isset($thanhphanchinh)){
                            $thanhphanchinh = new Thanhphan;
                            $thanhphanchinh->slug = last($thanh_phan_chinh_list);
                            $thanhphanchinh->ten = $thanh_phan_chinh->innertext;
                            $thanhphanchinh->save();
                        }
                        \DB::table('thuoc_thanhphan')->insertOrIgnore([
                            ['thuoc_id' => $thuoc->id, 'thanhphan_id' => $thanhphanchinh->id]
                        ]);
                    }
                }else if (str_contains($attr->innertext, 'Dạng bào chế:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Dạng bào chế:')){
                        // Dạng bào chế:
                        $dang_bao_che = $attr->find('td', 1)->innertext;

                        $dangbaoche = Dangbaoche::where('ten', $dang_bao_che)->first();

                        if(!isset($dangbaoche)){
                            $dangbaoche = new Dangbaoche;
                            $dangbaoche->ten = $dang_bao_che;
                            $dangbaoche->save();
                        }

                        $thuoc->dangbaoche_id = $dangbaoche->id;
                    }
                }else if (str_contains($attr->innertext, 'Quy cách:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Quy cách:')){
                        // Quy cách:
                        $quy_cach = $attr->find('td', 1)->innertext;

                        $quycach = Quycach::where('ten', $quy_cach)->first();

                        if(!isset($quycach)){
                            $quycach = new Quycach;
                            $quycach->ten = $quy_cach;
                            $quycach->save();
                        }

                        $thuoc->quycach_id = $quycach->id;
                    }
                }else if (str_contains($attr->innertext, 'Chỉ định:')) { 
                    // Chỉ định:
                    $chi_dinh = $attr->find('td', 1);

                    $thuoc->chidinh = $chi_dinh->innertext;

                }else if (str_contains($attr->innertext, 'Xuất xứ thương hiệu:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Xuất xứ thương hiệu:')){
                        // Xuất xứ thương hiệu:
                        $xuatxuthuonghieu = $attr->find('td', 1)->innertext;

                        $xuatxu = Xuatxu::where('ten', $xuatxuthuonghieu)->first();

                        if(!isset($xuatxu)){
                            $xuatxu = new Xuatxu;
                            $xuatxu->ten = $xuatxuthuonghieu;
                            $xuatxu->save();
                        }

                        $thuoc->xuatxu_id = $xuatxu->id;
                    }
                }else if (str_contains($attr->innertext, 'Nhà sản xuất:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Nhà sản xuất:')){
                        // Nhà sản xuất:
                        $nha_san_xuat = $attr->find('td', 1)->innertext;

                        $nhasanxuat = Nhasanxuat::where('ten', $nha_san_xuat)->first();

                        if(!isset($nhasanxuat)){
                            $nhasanxuat = new Nhasanxuat;
                            $nhasanxuat->ten = $nha_san_xuat;
                            $nhasanxuat->save();
                        }

                        \DB::table('thuoc_nhasanxuat')->insertOrIgnore([
                            ['thuoc_id' => $thuoc->id, 'nhasanxuat_id' => $nhasanxuat->id]
                        ]);
                    }
                }else if (str_contains($attr->innertext, 'Công dụng:')) { 
                    // Công dụng:
                    $cong_dung = $attr->find('td', 1)->innertext;

                    $thuoc->congdung = $cong_dung;
                }else if (str_contains($attr->innertext, 'Thuốc cần kê toa:')) { 
                    // Thuốc cần kê toa:
                    $thuoc_can_ke_toa = $attr->find('td', 1)->innertext;

                    $thuoc->thuoccanketoa = $thuoc_can_ke_toa;
                }else if (str_contains($attr->innertext, 'Số đăng ký:')) { 
                    // Số đăng ký:
                    $so_dang_ky = $attr->find('td', 1)->innertext;

                    $thuoc->sodangky = $so_dang_ky;
                }else if (str_contains($attr->innertext, 'Nước sản xuất:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Nước sản xuất:')){
                        // Nước sản xuất:
                        $noi_san_xuat = $attr->find('td', 1)->innertext;

                        $noisanxuat = Noisanxuat::where('ten', $noi_san_xuat)->first();

                        if(!isset($noisanxuat)){
                            $noisanxuat = new Noisanxuat;
                            $noisanxuat->ten = $noi_san_xuat;
                            $noisanxuat->save();
                        }

                        $thuoc->noisanxuat_id = $noisanxuat->id;
                    }
                }else if (str_contains($attr->innertext, 'Độ tuổi:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Độ tuổi:')){
                        // Lưu ý:
                        $do_tuoi = $attr->find('td', 1)->innertext;

                        $thuoc->dotuoi = $do_tuoi;
                    }
                }else if (str_contains($attr->innertext, 'Cảnh báo:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Cảnh báo:')){
                        // Lưu ý:
                        $canh_bao = $attr->find('td', 1)->innertext;

                        $thuoc->canhbao = $canh_bao;
                    }
                }else if (str_contains($attr->innertext, 'Chống chỉ định:')) { 
                    if(str_contains($attr->find('td', 0)->innertext, 'Chống chỉ định:')){
                        // Lưu ý:
                        $chong_chi_dinh = $attr->find('td', 1)->innertext;

                        $thuoc->chongchidinh = $chong_chi_dinh;
                    }
                }
            }

            // process thuonghieu
            $thuong_hieu = $html->find('.lc-detail-brand a',0);

            if($thuong_hieu){
                $thuonghieu = Thuonghieu::where('ten', $thuong_hieu->innertext)->first();

                if($thuonghieu){
                    $thuoc->thuonghieu_id = $thuonghieu->id;
                }else{
                    $thuonghieu = new Thuonghieu;
                    $thuonghieu->slug = substr($thuong_hieu->href, 41);
                    $thuonghieu->ten = $thuong_hieu->innertext;
                    $thuonghieu->save();
                    $thuoc->thuonghieu_id = $thuonghieu->id;
                }
            }

            // process motasanpham
            $mo_ta_san_pham = $html->find('.typho-MoTaSanPham',0);
            if($mo_ta_san_pham){
                $thuoc->motasanpham = $mo_ta_san_pham->innertext;
            }

            // process duocchat
            $duoc_chat = $html->find('.typho-ThanhPhan',0);
            if($duoc_chat){
                $thuoc->duocchat = $duoc_chat->innertext;
            }

            // process motasanpham
            $cong_dung = $html->find('.typho-CongDung',0);
            if($cong_dung){
                $thuoc->congdung2 = $cong_dung->innertext;
            }

            // process motasanpham
            $lieu_dung = $html->find('.typho-LieuDung',0);
            if($lieu_dung){
                $thuoc->lieuDung = $lieu_dung->innertext;
            }

            // process motasanpham
            $tac_dung_phu = $html->find('.typho-TacDungPhu',0);
            if($tac_dung_phu){
                $thuoc->tacdungphu = $tac_dung_phu->innertext;
            }

            // process motasanpham
            $luuy = $html->find('.typho-luuy',0);
            if($luuy){
                $thuoc->luuy2 = $luuy->innertext;
            }

            // process motasanpham
            $baoQuan = $html->find('.typho-BaoQuan',0);
            if($baoQuan){
                $thuoc->baoquan = $baoQuan->innertext;
            }

            // process motasanpham
            $nguonThamKhao = $html->find('.typho-NguonThamKhao',0);
            if($nguonThamKhao){
                $thuoc->nguonthamkhao = $nguonThamKhao->innertext;
            }

            $thuoc->save();

            $link->status = 1;
            $link->save();
            // clean up memory
            $html->clear();
            unset($html);

            // header('Location: '.$_SERVER['REQUEST_URI']);
        }
        header("refresh: 0.1");
    }

    public function crawl3(){
        include_once('simple_html_dom.php');

        $thuocs = Thuoc::where('status', -1)->take(100)->get();
        foreach($thuocs as $thuoc){
            $html = file_get_html('https://nhathuoclongchau.com' . $thuoc->link);
            $bigImages = $html->find('.swiper-slide');
            $dir = public_path() . '/images/';
            $links = [];
            Helper::checkFolder($dir . "$thuoc->id");
            foreach($bigImages as $image){
                if(isset($image->attr['data-src'])){
                    $imageUrl = $image->attr['data-src'];
                    // $imageUrl = $imageUrl . '_large.JPG';
                    $imageName = last(explode("/", $imageUrl));
                    file_put_contents($dir . "$thuoc->id/" . $imageName, file_get_contents($imageUrl));
                    $links[] = "/$thuoc->id/" . $imageName;
                }
            }

            $thuoc->images = json_encode($links);
            $thuoc->status = 1;
            $thuoc->save();
            // clean up memory
            $html->clear();
            unset($html);
        }

        header("refresh: 0.1");

        // dd($bigImage);
    }

    public function processThuoc(){
        
    }

    public function test(){
        $source = 'vi';
        $target = 'en';
        $text = 'Xin chào';
        dd(Helper::requestTranslation($source, $target, $text));
    }
}
