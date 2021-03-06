<?php if ( ! defined('BASEPATH')) exit('Низзя!');

class Articles_model extends Common_model {

  public $page=array();

  public $pagenav='';

  public $artsperpage=0;

    public function __construct()
    {
        parent::__construct();

        $this->artsperpage=$this->common_model->get_page_blocks(array(11));
    }


    public function get_category()
    {
    $array=array('news'=>array(),'articles'=>array());
     $query=$this->db->get('articles_category');
     if($query->num_rows()>0){
       foreach($query->result_array() as $key=>$cat){
        $array[$cat['type']][]=$cat;
       }
     }
     return $array;
    }

    public function get_cat_data($id)
    {
     $this->db->where('id', $id);
     $query=$this->db->get('articles_category');
     if($query->num_rows()>0){
       return $query->row_array();
     }
       return array('id'=>0);
    }

    public function get_article_data($id)
    {
     $this->db->where('id', $id);
     $this->db->where('type', 'article');
     $query=$this->db->get('page');
     if($query->num_rows()>0){
       return $query->row_array();
     }
       return array('id'=>0, 'subtype'=>$this->uri->segment(3));
    }

    public function set_cat_rel($data)
    {

    $this->db->where('page_id', $data['page_id']);
    $this->db->delete('articles_rel');

    $this->db->insert('articles_rel', $data);

    }

    public function get_curr_cat_npnum($page_id)
    {
     $this->db->where('page_id', $page_id);
     $query=$this->db->get('articles_rel');
     if($query->num_rows()>0){
        $row=$query->row_array();
        return $row;
     }
        return false;
    }




    public function get_article($subtype){
        $this->db->where('id',$this->uri->segment(2));
        $this->db->where('subtype',$subtype);
        $query=$this->db->get('page');
        return $query->row_array();
    }

    public function get_last_articles($subtype=false, $day_theme=0)
    {

    if($subtype){$count_subtype=" and `subtype`='$subtype'";}else{$count_subtype="";}
    if($day_theme>0){$dtheme=" and `id`!=".$day_theme;}else{$dtheme="";}

     $query=$this->db->query("select count(*) from `page` where `type`='article' $count_subtype $dtheme");
     $count=current($query->row_array());
     $pagenav=$this->load->library('pagenav_lib',array('count'=>$count,'perpage'=>$this->artsperpage[11]['text']));

        $this->db->where('type','article');
        if($subtype){$this->db->where('subtype',$subtype);}
        if($day_theme>0){$this->db->where('id !=', $day_theme);}
        $this->db->order_by('id','desc');
        $query=$this->db->get('page',$pagenav->finish,$pagenav->start);
        $this->pagenav=&$pagenav;

        return $this->common_get_articles($query->result_array());
    }

    public function common_get_articles($result_array)
    {

    $this->load->model('imgs/imgs_model');

        $img_arr=array();

        foreach($result_array as $row){

        $img_arr[$row['id']]=current($this->imgs_model->get_imgs('page', $row['id']));
        }

        return array($result_array, $img_arr);
    }

    public function get_cat_articles($subtype, $cat_id)
    {
       $query=$this->db->query("select count(*) from `page` inner join `articles_rel` on `page`.id=`articles_rel`.page_id where `page`.`type`='article' and `page`.`subtype`='$subtype' and `articles_rel`.cat_id=".(int)$cat_id);
     $count=current($query->row_array());

     $pagenav=$this->load->library('pagenav_lib',array('count'=>$count,'perpage'=>$this->artsperpage[11]['text']));

        $query=$this->db->query("select page.* from `page` inner join `articles_rel` on `page`.id=`articles_rel`.page_id where `page`.`type`='article' and `page`.`subtype`='$subtype' and `articles_rel`.cat_id=".(int)$cat_id." order by id desc limit ".$pagenav->start.", ".$pagenav->finish);

        $this->pagenav=&$pagenav;

        return $this->common_get_articles($query->result_array());
    }

    public function get_arc_articles($subtype, $date)
    {

     $query=$this->db->query("select count(*) from `page` where `type`='article' and `subtype`='$subtype' and `timestamp`>=".(int)strtotime($date.' 00:00')." and `timestamp`<=".(int)strtotime($date.' 23:59'));
     $count=current($query->row_array());

     $pagenav=$this->load->library('pagenav_lib',array('count'=>$count,'perpage'=>$this->artsperpage[11]['text']));

        $query=$this->db->query("select * from `page` where `type`='article' and `subtype`='$subtype' and `timestamp`>=".(int)strtotime($date.' 00:00')." and `timestamp`<=".(int)strtotime($date.' 23:59')." order by id desc limit ".$pagenav->start.", ".$pagenav->finish);

        $this->pagenav=&$pagenav;

        return $this->common_get_articles($query->result_array());
    }

    public function check_calendar_date($date, $subtype)
    {

      $query=$this->db->query("select count(*) from `page` where `type`='article' and `subtype`='$subtype' and `timestamp`>=".(int)strtotime($date.' 00:00')." and `timestamp`<=".(int)strtotime($date.' 23:59'));
      return (int)current($query->row_array());
    }

    public function count_arts_in_cat($cats, $subtype)
    {
      foreach($cats as $cat){
      $query=$this->db->query("select count(*) from `articles_rel` where `type`='$subtype' and `cat_id`=".(int)$cat['id']);

$cat['count']=current($query->row_array());
$cats_full[$cat['id']]=$cat;
}
      return $cats_full;
    }

    public function get_theme_of_day()
    {
     $this->db->where('day_theme', '1');
     $query=$this->db->get('articles_rel');
     if($tday=$query->row_array()){
     $this->db->where('id', $tday['page_id']);
     $query=$this->db->get('page');
     }

     return $this->common_get_articles($query->result_array());
    }

     public function set_theme_of_day($set, $id)
    {
     if($set==1){
     $this->common_model->update_field_by_val('articles_rel', 'day_theme', 1, 0);
     $this->common_model->db_ins_upd('articles_rel', 'page_id', array('page_id'=>$id,'day_theme'=>1));
     }else{
     $this->common_model->db_ins_upd('articles_rel', 'page_id', array('page_id'=>$id,'day_theme'=>0));
     }
    }

    public function get_calendar_days()
    {
     $types=array('news'=>'','articles'=>'');
     foreach($types as $type=>$val){

     $query=$this->db->query("select DATE_FORMAT(FROM_UNIXTIME(`timestamp`), '%d.%m.%Y') as ddate from `page` where `type`='article' and `subtype`='$type' group by ddate");
     foreach($query->result_array() as $row){
       $types[$type].=',\''.$row['ddate']."'";
      }
     }
     $types['news']='glob_calendar_arr[0]=['.substr($types['news'],1).'];';
     $types['articles']='glob_calendar_arr[1]=['.substr($types['articles'],1).'];';
     return $types;
    }

}

?>