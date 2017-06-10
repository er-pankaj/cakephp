<?php
namespace App\Controller;
use App\Controller\AppController;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Cache\Cache;
use Cake\Mailer\Email;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\Validation\Validator;

ob_start();
class HomeController extends AppController
{
    public $paginate = [
        'maxLimit' => 15
    ];


    public function beforeFilter(Event $event)
    {
        $this->Auth->allow(['index','Signup','login','verifyEmail','freelancerSignup','saloonSignup','userForgotPassword','resetPassword',
                    'verifySecondaryEmail','faqs','freelancerBooking','getSubServices','getFreelancerList','getFreelancer','freelancerProfile','ajaxLogin','ajaxSignup','salonBooking','getSalonList','getSalonStaffList','getStylist','getSubServicesForSalon','sitemap','termsAndCondition','salonProfile','stylistProfile','getAvailability','viewSalonRatings','viewFreelancerRatings',
                            'viewStylistRatings','getAddresses','getCards','bookingRequestHandler','getSalonAddress','joinUs','getAvailableSlots','getSlots', 'comingSoon']);
    }

    public function initialize() {
        parent::initialize();
        $this->loadComponent('Auth', [
            'loginAction' => [
                'controller' => 'home',
                'action' => 'login',
            ],
            'authError' => 'Did you really think you are allowed to see that?',
            'authenticate' => [
                'Form' => [
                    'fields' => ['username' => 'email', 'password' => 'password'],
                ]
            ],
            'storage' => 'Session'
        ]);
    }

    public function index()
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','StyleBrigade');
        $this->loadModel('Cms');
        $banner = $this->Cms->find()
                        ->hydrate(false)
                        ->where(['id' => '10'])
                        ->andWhere(['status' =>'Active'])
                        ->first();
        $bgImage = $this->Cms->find()
                        ->select(['image1','id'])
                        ->hydrate(false)
                        ->where(['id' => '14'])
                        ->andWhere(['status' =>'Active'])
                        ->first();
        if(!empty($bgImage['image1']) && file_exists(WWW_ROOT.'img/homePageImages/'.$bgImage['image1']) ) {
            $bImage = HTTP_ROOT.'img/homePageImages/'.$bgImage['image1'];
        } else {
            $bImage = HTTP_ROOT.'img/staticImage/no-bgImage.png';
        }

        $hoffer =$this->Cms->find()
                ->hydrate(false)
                ->where(['id ' =>'5'])
                ->where(['status' => 'Active'])
                ->first();
        $this->set('hoffer',$hoffer);

        $iid = ['6','7','8'];
        $offer =$this->Cms->find()
                ->hydrate(false)
                ->where(['id IN ' => $iid])
                ->andwhere(['status' => 'Active'])
                ->toArray();
        $this->set('offer',$offer);


        $hid = ['2','3','4'];
        $works = $this->Cms->find()
                      ->hydrate(false)
                      ->where(['id IN' => $hid])
                      ->andWhere(['status' => 'Active'])
                      ->toArray();
        $contact = $this->Cms->find()
                        ->hydrate(false)
                        ->where(['id' => '9'])
                        ->andWhere(['status' => 'Active'])
                        ->first();

        $aboutUs = $this->Cms->find()
                    ->hydrate(false)
                    ->where(['id' => '1'])
                    ->andwhere(['status' => 'Active'])
                    ->first();
        $this->set('aboutUs',$aboutUs);
        $this->set('works',$works);
        $this->set('cover',$banner);
        $this->set('contact', $contact);
        $this->set('bImage',$bImage);
        $this->loadModel('Contacts');
        if ($this->request->is('post')) {
            $data = $this->request->data;
            $temp = $this->Contacts->newEntity($data);
            if($this->Contacts->save($temp)) {
                $this->Flash->success('Thank you for contacting with us, we will get back  you soon');
                $this->redirect('/home');
            }
        }

    }

    public function Signup()
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','SignUp');
        $this->request->session()->write('Auth.User');
        if($this->request->is('post'))  {

            $this->loadModel('Users');
            $data = $this->request->data;
            $errors = $this->Users->Signup($data);
            if(!empty($errors)) {
                $this->set('errors',$errors);
                $this->Flash->error('Please enter valid information.');
                $this->redirect(HTTP_ROOT.'home/login');

            }   else {
                    $this->Flash->info('We have sent you an email. Please click on the verification link.');
                    $this->redirect(HTTP_ROOT.'home/registration-success');

            }
        }
    }

    public function freelancerSignup($member_id = null)
    {
        $member_id = convert_uudecode(base64_decode($member_id));
        $this->loadModel('Services');
        $this->viewBuilder()->layout("public");
        $this->set('title','Freelancer SignUp');
        $this->set('member_id',$member_id);
        $servicesInfo = $this->Services->servicesInfo();
        $this->set(compact('servicesInfo'));
        $this->request->session()->write('Auth.User');
        if($this->request->is('post'))  {
            $this->loadModel('Users');
            $data = $this->request->data;

            $val = $this->Users->freelancerSignup($data);
            if($val == 'success'){
                $this->loadModel('AdminNotifications');
                $url = HTTP_ROOT.'admin/users/freelancerManagement'.$member_id;
                $title ="New Stylist Registered!";
                $this->loadModel('AdminNotifications');
                $notify = $this->AdminNotifications->newEntity();
                $notify->notification_title = $title;
                $notify->notification_url = $url;
                $notify->created = date('Y-m-d H:i:s');
                $this->AdminNotifications->save($notify);

                $this->Flash->info('Your Registration Process Completed Please Wait for Admin Approval');

                $this->redirect(HTTP_ROOT);
            }   else   {
                $this->Flash->error('Some Problem Found Please Try again later');
                $this->redirect(HTTP_ROOT);
            }
        }
    }

    public function saloonSignup($member_id = null)
    {
        $member_id = convert_uudecode(base64_decode($member_id));
        $this->loadModel('Services');
        $this->viewBuilder()->layout("public");
        $this->set('title','Saloon SignUp');
        $this->set(compact('member_id'));
        $servicesInfo = $this->Services->servicesInfo();
        $this->set(compact('servicesInfo'));
        $this->request->session()->write('Auth.User');
        if($this->request->is('post'))  {
            $this->loadModel('Users');
            $data = $this->request->data;

            $val = $this->Users->saloonSignup($data);
            if($val == 'success')   {
                $this->loadModel('AdminNotifications');
                $url = HTTP_ROOT.'admin/users/saloonManagement'.$member_id;
                $title ="New Salon Registered!";
                $this->loadModel('AdminNotifications');
                $notify = $this->AdminNotifications->newEntity();
                $notify->notification_title = $title;
                $notify->notification_url = $url;
                $notify->created = date('Y-m-d H:i:s');
                $this->AdminNotifications->save($notify);

                $this->redirect(HTTP_ROOT);
                $this->Flash->info('Your Registration Process Completed Please Wait for Admin Approval');


            }   else  {

                $this->redirect(HTTP_ROOT);
                $this->Flash->error('Some Problem Found Please Try again later');

            }
        }
    }

    public function verifyEmail($uid = null, $email = null)
    {
        $id = convert_uudecode(base64_decode($uid));
        $member_id = $id;
        $this->loadModel('Users');
        $query = $this->Users->find()
        ->where(['id'=>$id])
        ->hydrate(false)
        ->first();
        if($query['email_verify'] == 1) {
            $this->Flash->info('Link is Expired.');
            $this->redirect(HTTP_ROOT);
        }   elseif(sha1($query['email']) == $email)     {
                if($query['user_type'] == "user")   {
                    $temp = array();
                    $temp['Users']['id'] = $query['id'];
                    $temp['Users']['email_verify'] = 1;
                    $temp['Users']['status'] = 'Active';
                    $temp['Users']['profile_status'] = 'Complete';
                    $temp['Users']['admin_status'] = 'Approved';
                    $temp['Users']['created'] = date('Y-m-d H:m:s');
                }   else {
                    $temp = array();
                    $temp['Users']['id'] = $query['id'];
                    $temp['Users']['email_verify'] = 1;
                }
                $updateInfo = $this->Users->newEntity($temp);
                $this->Users->save($updateInfo);

                $info = $this->Users->find()
                    ->where(['id'=>$id])
                    ->hydrate(false)
                    ->first();

                $this->request->session()->write('Auth.User.id',$info['id']);
                $this->request->session()->write('Auth.User.email',$info['email']);
                $this->request->session()->write('Auth.User.user_type',$info['user_type']);
                $this->request->session()->write('Auth.User.first_name',$info['first_name']);
                $this->request->session()->write('Auth.User.last_name',$info['last_name']);

                if($info['user_type'] == "user")    {
                    if($info['email_verify'] == "1"
                        && $info['status'] == 'Active'
                        && $info['profile_status'] == 'Complete'
                        && $info['admin_status'] == "Approved")  {
                            return $this->redirect($this->Auth->redirectUrl(HTTP_ROOT.'members/dashboard'));
                        }   else   {
                                $this->Flash->error('Please verify your email');
                                $this->redirect(HTTP_ROOT.'home/login');
                            }
                }   elseif($info['user_type'] == "saloon")  {
                        if($info['email_verify'] == "1" )   {
                            return $this->redirect($this->Auth->redirectUrl(HTTP_ROOT.'home/saloonSignup/'.$uid));
                        }
                }   elseif($info['user_type'] == "freelancer")  {
                        if($info['email_verify'] == "1" )   {
                            return $this->redirect($this->Auth->redirectUrl(HTTP_ROOT.'home/freelancerSignup/'.$uid));
                        }
                }
        }
    }

    public function login()
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','Login');

        if($this->Cookie->check('Auth.User')) {
            $memInfo = $this->Cookie->read('Auth.User');
            $this->set('cookieData',$memInfo);
        }

        // $userCheck = $this->request->session()->read('Auth.User');

        // if($userCheck){
        //     return $this->redirect('/members/dashboard');
        // }

        if($this->request->is('post')) {

            $data = $this->request->data;
            $user = $this->Auth->identify();

            if($user['admin_status'] == 'Approved' && $user['profile_status'] == 'Complete') {
                if(isset($data['remember']) && !empty($data['remember']))   {
                    $cookie = array();
                    $cookie['username'] = $data['email'];
                    $cookie['password'] = $data['password'];
                    $this->Cookie->write('Auth.User', $cookie);
                } else {
                    $this->Cookie->delete('Auth.User');
                }

                $this->Auth->setUser($user);
                $userInfo = $this->request->session()->read('Auth.User');

                $member_id = $userInfo['id'];
                $member_id = $this->Utility->encode($member_id);
                if($userInfo['user_type'] == "user") {

                    if($userInfo['email_verify'] == "1") {

                        return $this->redirect($this->Auth->redirectUrl(HTTP_ROOT.'members/dashboard'));
                    }else {
                            $this->Flash->error('Please verify your email');
                            $this->redirect(HTTP_ROOT.'home/login');
                        }
                }   elseif ($userInfo['user_type'] == "freelancer") {
                        if($userInfo['profile_status'] == "Complete" && $userInfo['admin_status'] == "Approved"
                            && $userInfo['status'] == "Active") {
                            return $this->redirect($this->Auth->redirectUrl(HTTP_ROOT.'members/dashboard'));
                        } else {
                                $this->redirect(HTTP_ROOT.'home/freelancerSignup/'.$member_id);
                            }
                } elseif ($userInfo['user_type'] == "saloon") {
                    if ($userInfo['profile_status'] == "Complete" && $userInfo['admin_status'] == "Approved"
                        && $userInfo['status'] == "Active")   {
                        return $this->redirect($this->Auth->redirectUrl(HTTP_ROOT.'members/dashboard'));
                    } else {
                        $this->redirect(HTTP_ROOT.'home/saloonSignup/'.$member_id);
                    }
                }
            }
            // else {
            //     $this->Flash->error(__('Invalid username or password, try again'));
            // }
        }
    }

    public function userForgotPassword()
    {
        if($this->request->is('post')) {
            $data = $this->request->data;
            if(isset($data) && !empty($data)) {
                $this->loadModel('Users');
                $check = $this->Users->forgotPass($data);
                if($check == "success") {
                    $this->Flash->success('Reset Password link has been sent in your email id.');

                    return $this->redirect([
                        'action' => 'login'
                    ]);
                } else {
                    $this->Flash->error('We will send message if email exits');
                    return $this->redirect([
                        'action' => 'login'
                    ]);
                }
            }
        }
    }

    public function resetPassword($id = null, $email = null, $token = null)
    {
        $this->set('title','User Reset Password');
        $this->viewBuilder()->layout('public');
        $this->loadModel('Users');
        $uid = convert_uudecode(base64_decode($id));
        $this->set('uid',$uid);
        $linkInfo = $this->Users->find()
        ->where(['id'=>$uid])
        ->select(['id','activation_key'])
        ->first();
        if($linkInfo['activation_key'] == $token )  {
            if($this->request->is('post'))  {
                $data = $this->request->data;
                $this->Users->resetPass($data);
                $this->Flash->success('Your Password Reset Successfully');
                $this->redirect(HTTP_ROOT.'home/login');
            }
        } else {
            $this->Flash->info('Your Reset Password link has been expired you can not access this page');
            $this->redirect(HTTP_ROOT.'home/login');
        }
    }

    public function verifySecondaryEmail($rec)
    {
        if(!empty($rec)) {
            $this->loadModel('SecondaryEmails');
            $this->loadModel('Users');
            $this->loadComponent('Utility');
            $secEmail = $this->SecondaryEmails->find()
                ->where(array('token' => $rec));
            $emailExists = $secEmail->count();
            if($emailExists == 1) {
                $this->loadModel('Users');
                $secInfo = $secEmail->first();
                $temp['SecondaryEmails']['id'] = $secInfo['id'];
                $temp['SecondaryEmails']['token'] = '';
                $userId = $secInfo['user_id'];
                $email = $secInfo['email'];
                $data['Users']['id'] = $userId;
                $data['Users']['email'] = $email;
                $data = $this->Users->newEntity($data);

                if($this->Users->save($data)) {
                    $temp = $this->SecondaryEmails->newEntity($temp);
                    $this->SecondaryEmails->save($temp);
                    $this->Flash->success('Email verification completed please login.');
                    $this->redirect(HTTP_ROOT.'home/login');
                }
            } else {
                $this->Flash->error('expired.');
            }
        } else {
            $this->Flash->error('Expired.');
        }
    }


    public function faqs()
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','Faqs | StyleBrigade.com.au');
        $this->loadModel('Faqs');
        $this->loadModel('FaqCategories');
        $this->paginate = ['limit'=>10];
        $query = $this->paginate($this->Faqs
            ->find('all')
            ->hydrate(false)
            ->where(['status'=>'Active'])
            ->order(['id' => 'DESC']));
        $faqInfo = $query->toArray();
        $queryInfo = $this->FaqCategories
            ->find('all');
        $queryInfo->select(['id','title']);
        $queryInfo->hydrate(false);
        $catData = $queryInfo->toArray();

        $this->set(compact('faqInfo','catData'));

        if($this->request->query){
            $data = $this->request->query;
            if(isset($data['id']) && !empty($data['id'])){
                $query = $this->paginate($this->Faqs
                    ->find('all')
                    ->where(['faq_category_id'=>$data['id'],'status'=>'Active'])
                    ->order(['id' => 'DESC']));
                $faqInfo = $query->toArray();
                $this->set(compact('faqInfo'));
            }
        }
    }

    /******* Freelancer Booking
                Deepak Rathore   ********/

    public function freelancerBooking($type = null)
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','Stylist Bookings');
        $this->loadModel('Services');
        $this->loadModel('Cms');
        $this->loadModel('UsersServices');
        $this->loadModel('SubServices');
        $this->loadModel('Regions');
        $this->loadModel('Areas');


        $query = $this->SubServices->find('list', ['keyField' => 'id', 'valueField' => 'service_id'])
            ->hydrate(false)
            ->where(['flat_discount !=' => '0'])
            ->group('service_id');
            //prx($query->toArray());

        $sIds = array_values($query->toArray());
        //prx($sIds);
        $query = $this->UsersServices->find();
        $services = $this->UsersServices->find()
            ->contain(['Services'])
            ->select([
                'check' => $query->newExpr()->addCase(
                    $query->newExpr()->add(['UsersServices.service_id in'=> $sIds]),
                    1,
                    'integer'
                ),
                'UsersServices.id',
                'service_id',
                'Services.name',
                'Services.id',
                'Services.description',
                'Services.icon',
                'Services.image'
            ])
            ->hydrate(false)
            ->matching('Users', function(\Cake\ORM\Query $q){
                $q->select('Users.user_type');
                $q->where([
                    'Users.user_type' => 'freelancer',
                ]);
                return $q;
            })
            ->group(['UsersServices.service_id'])
            ->toArray();

        $start = "";
        $end = "";
        $start = date('Y-m-d',strtotime('today'));
        $end = date('Y-m-d',strtotime('+6 days'));
        $lastDate = date('Y-m-d',strtotime('+27 days'));

        //calculating next week
        $nextWeekStart = date('Y-m-d', strtotime($start . '+1 week'));
        $nextWeekEnd = date('Y-m-d', strtotime($end . '+1 week'));

        $bookingHeader = $this->Cms->find()
                        ->where(['id' => 13 ])
                        ->first();

        $area = $this->Areas->find()
                ->hydrate(false)
                ->toArray();

        $region = $this->Regions->find()
                ->hydrate(false)
                ->toArray();

        $this->set(compact('services','start','end','nextWeekStart','nextWeekEnd','lastDate','bookingHeader','area','region'));
        if($this->request->is('ajax')) {
            $this->loadModel('Users');
                if($type == 'booking-options') {
                    $this->viewBuilder()->layout("ajax");
                    $this->render('/Element/frnt/booking');
                } else if($type == 'all') {

                    $data = $this->request->data;

                    $serviceId = $data['serviceId'];
                    $subServiceId = $data['subSurviceId'];

                    $condition = [];
                    if(isset($data['name']) && !empty($data['name'])) {
                        $condition['OR'][] = ["concat(trim(Users.first_name), ' ', trim(Users.last_name)) LIKE '%".trim($data['name'])."%'"];
                        $condition['OR'][] = ["trim(Users.description) LIKE '%".trim($data['name'])."%'"];
                        $condition['OR'][] = ["trim(Users.zip_code) LIKE '%".trim($data['name'])."%'"];
                    }
                    if(!empty($data['rate'])) {
                        $condition = array_merge($condition,array('Users.avg_rating' => $data['rate']));
                    }
                    if(!empty($data['region'])) {
                        $condition = array_merge($condition,array('Users.region_id' => $data['region']));
                    }
                    if(!empty($data['area'])) {
                        $condition = array_merge($condition,array('Users.area_id' => $data['area']));
                    }

                    $info = $this->Users->find()
                        ->distinct(['Users.id'])
                        ->contain(['ProfileImages'])
                        ->where(['Users.status'=>'Active',
                            'Users.admin_status'=>'Approved',
                            'Users.profile_status'=>'Complete',
                            'Users.user_type'=>'freelancer',
                            $condition
                        ])

                        ->matching('UsersServices',function ($qs) use ($serviceId, $subServiceId) {
                            return $qs
                                ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                        })
                        ->hydrate(false)
                        ->toArray();
                        $this->set(compact('info'));
                        $this->viewBuilder()->layout("ajax");
                        $this->render('/Element/frnt/freelancer_listing');
                }
        }

    }

    public function getSubServices()
    {
        if($this->request->is('ajax')) {
            $data = $this->request->data;
            $this->loadModel('SubServices');
            $this->loadModel('FreelancerPricing');

            $subServices = $this->SubServices->find()
                ->where(['status'=>'Active', 'service_id'=>$data['id']])
                ->select(['id','name','price','price_share','flat_discount','duration'])
                ->toArray();


            $vstmePrice = $this->FreelancerPricing->find()
              ->hydrate(false)
              ->first();

            $info = '';
            if(!empty($subServices)) {
                foreach ($subServices as $list) {

                    $price  = $list['price'];
                    $additionalPrice = $vstmePrice['additional_price'] + $price ;
                    $flat_discount = $list['flat_discount'];
                    $discount = $price * ($flat_discount/100);
                    $discountedPrice = $price - $discount;
                    $discountedAdditionalPrice = $vstmePrice['additional_price'] + $discountedPrice ;

                    if($price == $discountedPrice) {

                        // $note = "<div class=\"chrge-note m-t-20\">
                        //         <p><span>Note:- These prices are for appointments at the stylists salon, an additional charge of $".$vstmePrice['additional_price']." will be applied to stylist visits to your chosen location</span></p>
                        //     </div>";

                        $info .= "<li data-name=\"".$list['name']."\" data-id=\"".$list['id']."\">
                                    <div class=\"subservc-desc\" data-rel=\"".$price."\"> <span class=\"title-span\">". $list['name']."</span></div>
                                    <div class=\"subservc-price\" data-rel=\"".$price."\"><span class=\"price-table\"><span class=\"price-table-cell\">$".$additionalPrice." </span></span>
                                    </div>
                                    <div class = \"dur-note\" data-rel=\"".$list['duration']."\"> ". $list['duration']." minutes</div>
                                </li>";
                    } else {

                        $info .= "<li data-name=\"".$list['name']."\" data-id=\"".$list['id']."\">
                                    <div class=\"subservc-desc\" data-rel=\"".$price."\"><span class=\"title-span\">". $list['name']."</span></div> <div class=\"\"> </div>
                                    <div class=\"subservc-price\"  data-rel=\"".$discountedPrice."\"><span class=\"price-table\"><span class=\"price-table-cell\">$".$discountedAdditionalPrice."  <s>$".$additionalPrice."</s></span></span></div>
                                    <div class = \"dur-note\" data-rel=\"".$list['duration']."\"> ". $list['duration']." minutes </div>
                                </li>";
                        }
                    }

            } else {
                // $note = '';
                $info .=  "<div class='no-service-message'>
                                Sorry, No services available in this category.
                           </div>";
            }
            echo $this->sendAjaxResponse('success',$info);
            die;

        }
    }

    public function getFreelancerList($type=null)
    {
        if($this->request->is('ajax')) {
            $this->loadModel('Users');
            $this->loadModel('ActualAvailability');
            if($this->request->is('post'))
            {
                $data = $this->request->data;
                $startDate = $data['date'];
                $startTime = $data['startTime'];
                $serviceId = $data['serviceId'];
                $subServiceId = $data['subSurviceId'];
                $duration = $data['duration'];
                $endTime = date('H:i:s', strtotime('+'.$duration . 'minutes' . $startTime));
                $startTime = date('H:i:s', strtotime($startTime));

                $users = $this->Users->find()
                    ->hydrate(false)
                    ->distinct(['Users.id'])
                    ->where([
                        'Users.status' => 'active',
                        'Users.user_type' => 'freelancer',
                    ])
                    ->matching('UsersServices', function($q) use ($serviceId, $subServiceId) {
                        return $q->where([
                            'UsersServices.service_id' => $serviceId,
                            'UsersServices.sub_service_id' => $subServiceId,
                        ]);
                    })
                    ->matching('ActualAvailability', function($q) use ($startDate, $endTime, $startTime) {
                        return $q->where([
                            'ActualAvailability.date' => $startDate,
                            'ActualAvailability.start_time >=' => $startTime,
                            'ActualAvailability.start_time <=' => $endTime,
                            'ActualAvailability.status' => 'Available'
                        ]);
                    });

                $freelancerAllList = $users->all();
                $freelancerList = "";
                foreach($freelancerAllList as $freelancer) {
                    $end_time = date('H:i:s', strtotime('+'.$duration.'minutes'.$startTime));

                    $slots = $this->ActualAvailability->find()
                        ->where(['ActualAvailability.user_id' => $freelancer['id'],
                            'ActualAvailability.date' => $startDate,
                            'ActualAvailability.start_time >=' => $startTime,
                            'ActualAvailability.end_time <=' => $end_time,
                            'ActualAvailability.status' => 'Available'])
                        ->hydrate(false)
                        ->count();

                    $requiredSlots = 1;
                    if($duration > 15) {
                        $requiredSlots = ceil($duration / 15);
                    }

                    if(!empty($slots) && ($slots >= $requiredSlots)) {
                        $profileImage = HTTP_ROOT.'img/staticImage/default_user.png';

                            if( !empty($freelancer['image']) && file_exists(WWW_ROOT.'img/profilePic/'.$freelancer['image']))
                            {
                                $profileImage = HTTP_ROOT.'img/profilePic/'.$freelancer['image'];
                            }

                            $freelancerList .= "<div class=\"col-md-4 col-sm-6 col-xs-12 fixed-heigh-div\">
                                <div class=\"servc-profile\">
                                    <img src=\"".$profileImage."\" class=\"img-responsive\" />
                                    <div class=\"col-md-12 col-sm-12 col-xs-12\">
                                        <h4>".$freelancer['first_name'].' '.$freelancer['last_name']."</h4>
                                        <span class=\"star-img\">
                                            <span data-r= \"".$freelancer['avg_rating']."\" class=\"fRating tRating\" >
                                            </span>
                                        </span>
                                        <p class=\"text-left m-t-10\">".$freelancer['description']."</p>
                                        <div class=\"col-md-12 col-sm-12 col-xs-12 p-0\">
                                            <a class=\"btn custom-btn\" href=\"".HTTP_ROOT.'home/freelancer-profile/'.$freelancer['id']."\" target=\"_blank\" data-rel=\"".$freelancer['id']."\"> View Profile </a>
                                            <button class=\"btn custom-btn slct-btn\" type=\"button\" data-rel=\"".$freelancer['id']."\"> Select </button>
                                        </div>
                                    </div>
                                </div>
                            </div>";
                        //}
                    }
                }

                if(!empty($freelancerList)) {
                    echo $this->sendAjaxResponse('success', $freelancerList); die;
                } else {
                    echo $this->sendAjaxResponse('error',''); die;
                }
            } else {
                echo $this->sendAjaxResponse('error',''); die;
            }
        } else {
            echo $this->sendAjaxResponse('error',''); die;
        }
    }

    public function getSortedFreelancerList($type=null)
    {
        if($this->request->is('ajax')) {
            $this->loadModel('Users');

            if($this->request->is('post')) {

                $data = $this->request->data;
                $serviceId = $data['serviceId'];
                $subServiceId = $data['subSurviceId'];

                $info = $this->Users->find()
                    ->distinct(['Users.id'])
                    ->where(['Users.status'=>'Active',
                        'Users.admin_status'=>'Approved',
                        'Users.profile_status'=>'Complete',
                        'Users.user_type'=>'freelancer',
                        'Users.avg_rating'=> $data['name']

                    ])

                    ->matching('UsersServices',function ($qs) use ($serviceId, $subServiceId) {
                                return $qs
                                    ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                            })
                    ->hydrate(false)
                    ->toArray();
            }
            $profileImage = HTTP_ROOT.'img/staticImage/default_user.png';
            $freelancerList = "";

            if(!empty($info)) {
                foreach ($info as $list) {
                    if( !empty($list['image']) && file_exists(WWW_ROOT.'img/profilePic/'.$list['image'])) {
                        $profileImage = HTTP_ROOT.'img/profilePic/'.$list['image'];
                    }
                    $freelancerList .= "<div class=\"col-md-3 col-sm-6 col-xs-12 fixed-heigh-div\">
                                        <div class=\"servc-profile\">
                                            <img src=\"".$profileImage."\" class=\"img-responsive\" />
                                            <div class=\"col-md-12 col-sm-12 col-xs-12\">
                                                <h4>".$list['first_name'].' '.$list['last_name']."</h4>
                                                <span class=\"star-img\">
                                                    <span data-r= \"".$list['avg_rating']."\" class=\"fRating tRating\" >
                                                    </span>
                                                </span>
                                                <p class=\"text-left m-t-10\">".$list['description']."</p>
                                                <div class=\"col-md-12 col-sm-12 col-xs-12 p-0\">
                                                    <a class=\"btn custom-btn\" href=\"".HTTP_ROOT.'home/freelancer-profile/'.$list['id']."\" target=\"_blank\" data-rel=\"".$list['id']."\"> View Profile </a>
                                                    <button class=\"btn custom-btn slct-btn\" type=\"button\" data-rel=\"".$list['id']."\"> Select </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                }
            } else {
                $freelancerList = "<div class='no-service-message'>No Stylist Available</div>";
            }

            echo $this->sendAjaxResponse('success',$freelancerList);
            die;
        } else {
            echo $this->sendAjaxResponse('error','');
            die;
        }
    }
    public function getAreaFreelancerList($type=null)
    {
        if($this->request->is('ajax')) {
            $this->loadModel('Users');

            if($this->request->is('post')) {

                $data = $this->request->data;
                $serviceId = $data['serviceId'];
                $subServiceId = $data['subSurviceId'];

                $info = $this->Users->find()
                    ->distinct(['Users.id'])
                    ->where(['Users.status'=>'Active',
                        'Users.admin_status'=>'Approved',
                        'Users.profile_status'=>'Complete',
                        'Users.user_type'=>'freelancer',
                        'Users.area_id'=> $data['name']
                    ])

                    ->matching('UsersServices',function ($qs) use ($serviceId, $subServiceId) {
                                return $qs
                                    ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                            })
                    ->hydrate(false)
                    ->toArray();
            }
            $profileImage = HTTP_ROOT.'img/staticImage/default_user.png';
            $freelancerList = "";

            if(!empty($info)) {
                foreach ($info as $list) {
                    if( !empty($list['image']) && file_exists(WWW_ROOT.'img/profilePic/'.$list['image'])) {
                        $profileImage = HTTP_ROOT.'img/profilePic/'.$list['image'];
                    }
                    $freelancerList .= "<div class=\"col-md-3 col-sm-6 col-xs-12 fixed-heigh-div\">
                                        <div class=\"servc-profile\">
                                            <img src=\"".$profileImage."\" class=\"img-responsive\" />
                                            <div class=\"col-md-12 col-sm-12 col-xs-12\">
                                                <h4>".$list['first_name'].' '.$list['last_name']."</h4>
                                                <span class=\"star-img\">
                                                    <span data-r= \"".$list['avg_rating']."\" class=\"fRating tRating\" >
                                                    </span>
                                                </span>
                                                <p class=\"text-left m-t-10\">".$list['description']."</p>
                                                <div class=\"col-md-12 col-sm-12 col-xs-12 p-0\">
                                                    <a class=\"btn custom-btn\" href=\"".HTTP_ROOT.'home/freelancer-profile/'.$list['id']."\" target=\"_blank\" data-rel=\"".$list['id']."\"> View Profile </a>
                                                    <button class=\"btn custom-btn slct-btn\" type=\"button\" data-rel=\"".$list['id']."\"> Select </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                }
            } else {
                $freelancerList = "<div class='no-service-message'>No Stylist Available</div>";
            }

            echo $this->sendAjaxResponse('success',$freelancerList);
            die;
        } else {
            echo $this->sendAjaxResponse('error','');
            die;
        }
    }

    public function getRegionFreelancerList($type=null)
    {
        if($this->request->is('ajax')) {
            $this->loadModel('Users');

            if($this->request->is('post')) {

                $data = $this->request->data;
                $serviceId = $data['serviceId'];
                $subServiceId = $data['subSurviceId'];

                $info = $this->Users->find()
                    ->distinct(['Users.id'])
                    ->where(['Users.status'=>'Active',
                        'Users.admin_status'=>'Approved',
                        'Users.profile_status'=>'Complete',
                        'Users.user_type'=>'freelancer',
                        'Users.area_id'=> $data['name']
                    ])

                    ->matching('UsersServices',function ($qs) use ($serviceId, $subServiceId) {
                                return $qs
                                    ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                            })
                    ->hydrate(false)
                    ->toArray();
            }
            $profileImage = HTTP_ROOT.'img/staticImage/default_user.png';
            $freelancerList = "";

            if(!empty($info)) {
                foreach ($info as $list) {
                    if( !empty($list['image']) && file_exists(WWW_ROOT.'img/profilePic/'.$list['image'])) {
                        $profileImage = HTTP_ROOT.'img/profilePic/'.$list['image'];
                    }
                    $freelancerList .= "<div class=\"col-md-3 col-sm-6 col-xs-12 fixed-heigh-div\">
                                        <div class=\"servc-profile\">
                                            <img src=\"".$profileImage."\" class=\"img-responsive\" />
                                            <div class=\"col-md-12 col-sm-12 col-xs-12\">
                                                <h4>".$list['first_name'].' '.$list['last_name']."</h4>
                                                <span class=\"star-img\">
                                                    <span data-r= \"".$list['avg_rating']."\" class=\"fRating tRating\" >
                                                    </span>
                                                </span>
                                                <p class=\"text-left m-t-10\">".$list['description']."</p>
                                                <div class=\"col-md-12 col-sm-12 col-xs-12 p-0\">
                                                    <a class=\"btn custom-btn\" href=\"".HTTP_ROOT.'home/freelancer-profile/'.$list['id']."\" target=\"_blank\" data-rel=\"".$list['id']."\"> View Profile </a>
                                                    <button class=\"btn custom-btn slct-btn\" type=\"button\" data-rel=\"".$list['id']."\"> Select </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                }
            } else {
                $freelancerList = "<div class='no-service-message'>No Stylist Available</div>";
            }

            echo $this->sendAjaxResponse('success',$freelancerList);
            die;
        } else {
            echo $this->sendAjaxResponse('error','');
            die;
        }
    }

    public function getFreelancer()
    {
        if($this->request->is('ajax')) {
            $this->loadModel('Users');

            $data = $this->request->data;

            $info = $this->Users->find()
                ->select(['id','first_name','last_name','image','avg_rating','description'])
                ->contain(['ProfileImages'])
                ->where(['status'=>'Active','admin_status'=>'Approved','profile_status'=>'Complete','user_type'=>'freelancer','id'=>$data['id']])
                ->first();

            if(!empty($info['profileImages'])) {
                $pstatus = $info['profileImages'][0]['profile_image_status'];
                $pstatus_image = $info['profileImages'][0]['profile_image'];
                $cstatus = $info['profileImages'][0]['cover_image_status'];
            }

            $profileImage = HTTP_ROOT.'img/staticImage/default_user.png';

            if(!empty($info['profileImages']) && $pstatus == "Active" && file_exists(WWW_ROOT.'img/profilePic/'.$pstatus_image)) {
                $profileImage = HTTP_ROOT.'img/profilePic/'.$pstatus_image;
            }

            $rating = $info['avg_rating'];

            $freelancerinfo = " <div class=\"col-lg-6 col-md-6 col-sm-6\">
                                    <div class=\"servc-profile m-t-20\">
                                        <img src=\"".$profileImage."\" class=\"img-responsive\" />
                                    </div>
                                    <div class=\"col-md-12 col-sm-12 col-xs-12 text-center p-0 m-b-20\">
                                        <a class=\"btn custom-btn\" href=\"".HTTP_ROOT.'home/freelancer-profile/'.$info['id']."\" target=\"_blank\"> View Profile </a>
                                    </div>
                                </div>
                                <div class=\"col-lg-6 col-md-6 col-sm-6 \">
                                    <div class=\"name-or-star-btn\">
                                        <h3>".$info['first_name'].' '.$info['last_name']."</h3>
                                        <p class=\"shrt-desc\">".$info['description']."</p>
                                        <span class=\"star-img\">
                                            <span data-r=\"".$rating."\" class=\"fRating tRating\" >
                                            </span>
                                        </span>
                                    </div>
                                </div>";

            echo $this->sendAjaxResponse('success',$freelancerinfo);
            die;
        } else {
            echo $this->sendAjaxResponse('error','');
            die;
        }
    }

    public function freelancerProfile($id = null)
    {
        $this->set('title','Freelancer Profile');
        $this->viewBuilder()->layout('public');
        if(!empty($id)) {
            $this->loadModel('Users');
            $this->loadModel('Ratings');
            $this->loadModel('Gallery');

            $info = $this->Users->find()
                ->contain(['UsersServices.Services','UsersServices.SubServices','ProfileImages'])
                ->where(['id'=>$id, 'admin_status'=>'Approved', 'status'=>'Active', 'user_type' => 'freelancer'])
                ->first();

            if(empty($info)) {
               //$this->Flash->error('Freelancer Not Found');
                return $this->redirect('/home');
            }

            $rating = $this->Ratings->find()
                ->contain(['Users'])
                ->where(['Ratings.freelancer_id'=>$id])
                ->limit(3)
                ->hydrate(false)
                ->order(['Ratings.id desc'])
                ->toArray();


            $average_rating = $this->Users->find()
                ->where(['Users.id'=>$id])
                ->select(['Users.avg_rating','Users.reviews'])
                ->hydrate(false)
                ->first();

            $imageGallery = $this->Gallery->find()
                ->where(['user_id' => $id, 'status' => 'Active'])
                ->hydrate(false)
                ->toArray();

            $this->set(compact('info','rating','average_rating','imageGallery'));
        } else {
            //$this->Flash->error('Freelancer Not Found');
            return $this->redirect('/home');
        }
    }

   public function ajaxLogin()
    {
        if($this->request->is('ajax')) {

            if($this->Cookie->check('Auth.User')) {
                $memInfo = $this->Cookie->read('Auth.User');
                $this->set('cookieData',$memInfo);
            }

            $userCheck = $this->request->session()->read('Auth.User');

            // if($userCheck) {
            //     return $this->redirect('/members/dashboard');
            // }

            if($this->request->is('post')) {
                $data = $this->request->data;
                $user = $this->Auth->identify();

                if($user) {
                    if($user['user_type'] == 'user') {
                        if(isset($data['remember']) && !empty($data['remember'])) {
                            $this->Cookie->delete('Auth.User');
                            $cookie = array();
                            $cookie['username'] = $data['email'];
                            $cookie['password'] = $data['password'];
                            $this->Cookie->write('Auth.User', $cookie);
                        } else {

                        }

                        $this->Auth->setUser($user);
                        $userInfo = $this->request->session()->read('Auth.User');
                        $member_id = $userInfo['id'];
                        $member_id = $this->Utility->encode($member_id);
                        if($userInfo['user_type'] == "user") {
                            if($userInfo['email_verify'] == "1") {
                                echo $this->sendAjaxResponse('success',['username' =>$userInfo['first_name'] ,
                                    'image' => $userInfo['image']]);
                                die;
                            }else {
                                echo $this->sendAjaxResponse('Not verify','');
                                die;
                            }
                        }
                    } else {
                        echo $this->sendAjaxResponse('Invalid','Invalid Users');
                        die;
                    }
                } else {
                    echo $this->sendAjaxResponse('Not found','Invalid Users');
                    die;
                }
            }
        }
    }

    function bookingConfirmationMail()
    {
        $this->loadModel('EmailTemplates');
        $userInfo = $this->request->session()->read('Auth.User');

        if($userInfo) {
            $queryInfo = $this->EmailTemplates
                ->find()
                ->where(array(
                        'id' =>'6'
                    )
                );

            $templateInfo = $queryInfo->first();
            $emailContent = $templateInfo['html_content'];
            $emailContent = str_replace('{firstname}',$userInfo['first_name'], $emailContent);
            $email = new Email();
            $email->viewVars(['emailContent' => $emailContent]);
            $email->template('common_template','default')
                ->emailFormat('html')
                ->to($userInfo['email'])
                ->from($templateInfo['from_email'],$templateInfo['from_name'])
                ->subject($templateInfo['subject'])
                ->send();
        }
        return $this->redirect('/members/dashboard');
    }

    public function ajaxSignup()
    {
        if($this->request->is('post'))  {
            $this->loadModel('Users');
            $data = $this->request->data;
            $errors = $this->Users->Signup($data);
                $user = $this->Users->find()
                    ->where(['email'=>$data['Users']['email2']])
                    ->first();

                if($user) {
                    if($user['user_type'] == 'user') {
                        unset($user['password']);
                        unset($user['decoded_password']);
                        // $this->request->session()->write('Auth.User',$user);
                        // $userInfo = $this->request->session()->read('Auth.User');
                        //if($userInfo['user_type'] == "user") {
                            $this->Flash->success('Verification link has been send to your email, Please verify to continue.');
                            echo $this->sendAjaxResponse('success','');
                            die;
                        //}
                    } else {
                        echo $this->sendAjaxResponse('Invalid','Invalid Users');
                        die;
                    }
                } else {
                    echo $this->sendAjaxResponse('Not found','Invalid Users');
                    die;
                }
        }
    }

    /****** Salon Booking
            Deepak Rathore *******/

    public function salonBooking($type=null)
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','Salon Bookings');
        $this->loadModel('Services');
        $this->loadModel('Cms');
        $this->loadModel('Users');
        $this->loadModel('Regions');
        $this->loadModel('Areas');

        $services = $this->Services->find()
            ->where(['status'=>'Active'])
            ->hydrate(false)
            ->toArray();

        $start = "";
        $end = "";

        $start = date('Y-m-d',strtotime('today'));
        $end = date('Y-m-d',strtotime('+6 days'));

        $lastDate = date('Y-m-d',strtotime('+27 days'));

        //calculating next week
        $nextWeekStart = date('Y-m-d', strtotime($start . '+1 week'));
        $nextWeekEnd = date('Y-m-d', strtotime($end . '+1 week'));

        $bookingHeader = $this->Cms->find()
                        ->where(['id' => 12 ])
                        ->first();

        $area = $this->Areas->find()
                ->hydrate(false)
                ->toArray();

        $region = $this->Regions->find()
                ->hydrate(false)
                ->toArray();


        $this->set(compact('services','start','end','nextWeekStart','nextWeekEnd','lastDate','bookingHeader','area','region'));

        if($this->request->is('ajax')) {
            $this->loadModel('Users');
            $this->loadModel('AddressBooks');
                if($type == 'booking-options') {
                    $this->viewBuilder()->layout("ajax");
                    $this->render('/Element/frnt/salon_booking_option');
                } else if($type == 'all') {
                    $data = $this->request->data;
                    $serviceId = $data['serviceId'];
                    $subServiceId = $data['subSurviceId'];

                    $condition = [];
                    if(isset($data['name']) && !empty($data['name'])) {
                        //$condition = ["concat(trim(Users.first_name), ' ', trim(Users.last_name)) LIKE '%".trim($data['name'])."%'"];
                        $condition['OR'][] = ["trim(Users.salon_name) LIKE '%".trim($data['name'])."%'"];
                        $condition['OR'][] = ["trim(Users.description) LIKE '%".trim($data['name'])."%'"];
                        $condition['OR'][] = ["trim(Users.zip_code) LIKE '%".trim($data['name'])."%'"];
                    }

                    if(!empty($data['rate'])) {
                        $condition = array_merge($condition,array('Users.avg_rating' => $data['rate']));
                    }
                    if(!empty($data['region'])) {
                        $condition = array_merge($condition,array('Users.region_id' => $data['region']));
                    }
                    if(!empty($data['area'])) {
                        $condition = array_merge($condition,array('Users.area_id' => $data['area']));
                    }

                    $info = $this->Users->find()
                        ->distinct(['Users.id'])
                        ->contain(['ProfileImages'])
                        ->where(['Users.status'=>'Active',
                            'Users.admin_status'=>'Approved',
                            'Users.profile_status'=>'Complete',
                            'Users.user_type'=>'saloon',
                            $condition
                        ])

                        ->matching('SalonCategoryPrices',function ($qs) use ($serviceId, $subServiceId) {
                            return $qs
                                ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                        })
                        ->hydrate(false)
                        ->toArray();


                    $this->set(compact('info'));
                    $this->viewBuilder()->layout("ajax");
                    $this->render('/Element/frnt/salon_listing');
                } else if($type == 'sorted') {

                    $data = $this->request->data;
                    $serviceId = $data['serviceId'];
                    $subServiceId = $data['subSurviceId'];


                    $info = $this->Users->find()
                        ->distinct(['Users.id'])
                        ->contain(['ProfileImages'])
                        ->where(['Users.status'=>'Active',
                            'Users.admin_status'=>'Approved',
                            'Users.profile_status'=>'Complete',
                            'Users.user_type'=>'saloon',
                            'Users.avg_rating' => $data['name']
                        ])

                        ->matching('SalonCategoryPrices',function ($qs) use ($serviceId, $subServiceId) {
                            return $qs
                                ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                        })
                        ->hydrate(false)
                        ->toArray();


                    $this->set(compact('info'));
                    $this->viewBuilder()->layout("ajax");
                    $this->render('/Element/frnt/salon_listing');
                }  else if($type == 'area') {
                    $data = $this->request->data;
                    $serviceId = $data['serviceId'];
                    $subServiceId = $data['subSurviceId'];

                    $info = $this->Users->find()
                        ->distinct(['Users.id'])
                        ->contain(['ProfileImages'])
                        ->where(['Users.status'=>'Active',
                            'Users.admin_status'=>'Approved',
                            'Users.profile_status'=>'Complete',
                            'Users.user_type'=>'saloon',
                            'Users.area_id' => $data['name']
                        ])

                        ->matching('SalonCategoryPrices',function ($qs) use ($serviceId, $subServiceId) {
                            return $qs
                                ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                        })
                        ->hydrate(false)
                        ->toArray();

                    $this->set(compact('info'));
                    $this->viewBuilder()->layout("ajax");
                    $this->render('/Element/frnt/salon_listing');
                }
                else if($type == 'region') {
                    $data = $this->request->data;
                    $serviceId = $data['serviceId'];
                    $subServiceId = $data['subSurviceId'];

                    $info = $this->Users->find()
                        ->distinct(['Users.id'])
                        ->contain(['ProfileImages'])
                        ->where(['Users.status'=>'Active',
                            'Users.admin_status'=>'Approved',
                            'Users.profile_status'=>'Complete',
                            'Users.user_type'=>'saloon',
                            'Users.region_id' => $data['name']
                        ])

                        ->matching('SalonCategoryPrices',function ($qs) use ($serviceId, $subServiceId) {
                            return $qs
                                ->where(['service_id' => $serviceId, 'sub_service_id' => $subServiceId]);
                        })
                        ->hydrate(false)
                        ->toArray();

                    $this->set(compact('info'));
                    $this->viewBuilder()->layout("ajax");
                    $this->render('/Element/frnt/salon_listing');
                }
                else if($type == 'staffList') {
                    $this->loadModel('SalonStaffMembers');
                    $this->loadModel('UsersServices');
                    $this->loadModel('StaffMemberServices');
                    $this->loadModel('SalonCategoryPrices');
                    $this->loadModel('Users');
                    $data = $this->request->data;

                    $serviceId = $data['serviceId'];
                    $subServiceId = $data['subSurviceId'];

                    $stylistList = $this->SalonCategoryPrices->find()
                        ->contain(['StylistCategories'])
                        ->where(['SalonCategoryPrices.salon_id'=> $data['id'],
                            'service_id' => $serviceId,
                            'sub_service_id' => $subServiceId])
                        ->hydrate(false)
                        ->toArray();
                    $salonFlatDiscount = $this->Users->find()
                        ->where(['Users.id'=> $data['id']])
                        ->hydrate(false)
                        ->first();

                    $serviceDuration = $this->UsersServices->find()
                        ->where(['UsersServices.user_id' => $data['id'],
                            'service_id'=> $serviceId,
                            'sub_service_id'=> $subServiceId])
                        ->hydrate(false)
                        ->first();

                    $duration = $serviceDuration['duration'];

                    $this->set(compact('stylistList','salonFlatDiscount','duration'));
                    $this->viewBuilder()->layout("ajax");
                    $this->render('/Element/frnt/stylist_listing');
                }
        }
        //$this->render('/Home/coming_soon');

    }

    public function getSubServicesForSalon()
    {
        if($this->request->is('ajax')) {

            $data = $this->request->data;
            $this->loadModel('SubServices');
            $this->loadModel('UsersServices');
            $subServices = $this->SubServices->find()
                ->where(['status'=>'Active', 'service_id'=>$data['id']])
                ->select(['id','name','price'])
                ->toArray();
            $duration = $this->UsersServices->find()
                ->where(['service_id'=>$data['id']])
                ->select(['id','user_id','service_id','sub_service_id','duration'])
                ->toArray();

            $info = '';
            if(!empty($subServices)) {
                foreach ($subServices as $list) {
                    $info .= "<li data-name=\"".$list['name']."\" data-id=\"".$list['id']."\">
                                <div class=\"subservc-desc\">". $list['name']."</div>
                            </li>";
                }
            } else {
                $info .=  "<div class=\"no-service-message\">
                                Sorry, No services available in this category.
                            </div>";
            }
            // <div class=\"subservc-price\"> ". $durations['duration']." minutes
            // </div>
            echo $this->sendAjaxResponse('success',$info);
            die;

        }
    }

    public function getSalonList()
    {
        if($this->request->is('ajax')) {
            $this->loadModel('Users');
            $this->loadModel('SalonCategoryPrices');
            $this->loadModel('ActualAvailability');
            $data = $this->request->data;
            $startDate = $data['date'];
            $startTime = $data['startTime'];
            $serviceId = $data['serviceId'];
            $subServiceId = $data['subSrvcId'];

            $condition = [];
            if(isset($data['name']) && !empty($data['name'])) {
                $condition = ["concat(trim(Users.first_name), ' ', trim(Users.last_name)) LIKE '%".trim($data['name'])."%'"];
            }

            $subServiceid = $data['subSrvcId'];
            $salonList = $this->ActualAvailability->find()
                ->contain(['Users'])
                ->where(['Users.user_type'=>'saloon',
                    'ActualAvailability.date'=>$startDate,'ActualAvailability.start_time'=>$startTime])
                ->hydrate(false)
                ->toArray();
            $slots = [];
            foreach($salonList as $list)
            {
                $this->loadModel('UsersServices');
                $serviceDuration = $this->UsersServices->find()
                    ->where(['user_id' => $list['user_id'],
                        'sub_service_id'=> $subServiceId,
                        'service_id' => $serviceId])
                    ->hydrate(false)
                    ->first();
                $duration = $serviceDuration['duration'];
                $end_time = date('H:i:s', strtotime('+'.$duration.'minutes'.$startTime));

                $slots = $this->ActualAvailability
                    ->find()
                    ->hydrate(false)
                    ->distinct(['ActualAvailability.user_id'])
                    ->where([
                        //'user_id' => $list['user_id'],
                        'date' => $startDate,
                        'end_time' => $end_time ,
                        'start_time >=' => $startTime,
                        'status' => 'Available'
                    ])
                    ->toArray();
            }

            if(!empty($slots)) {
                    $salonLists = "";
                    foreach($slots as $slot) {
                            $list = $this->Users->find()
                                ->select()
                                ->hydrate(false)
                                ->where(['Users.id' => $slot['user_id'],
                                        'Users.status' =>'Active'
                                    ])
                                ->first();

                            $price = $this->SalonCategoryPrices->find()
                                ->where(['salon_id'=> $list['id'],
                                        'service_id' => $serviceId,
                                        'sub_service_id' => $subServiceId])
                                ->hydrate(false)
                                ->first();
                            $servicePrice = $price['price'];

                            $profileImage = HTTP_ROOT.'img/staticImage/default_user.png';
                            $rating = $list['avg_rating'];

                            if( !empty($list['image']) && file_exists(WWW_ROOT.'img/profilePic/'.$list['image'])) {
                                $profileImage = HTTP_ROOT.'img/profilePic/'.$list['image'];
                            }


                            $badge = '';
                            if($list['flat_discount'] > 0) {
                                $badge = '<div class="badge-disconut">** Special Offers **</div>';
                            }

                            $salonLists  .= "<div class=\"col-md-4 col-sm-6 col-xs-12 fixed-heigh-div\">
                                                $badge
                                                <div class=\"servc-profile\">
                                                    <img  src=\"".$profileImage."\" class=\"img-responsive\" />
                                                    <div class=\"col-md-12 col-sm-12 col-xs-12\">
                                                        <h4>".$list['salon_name']."</h4>
                                                        <span class=\"star-img\">
                                                            <span data-r=\"".$rating."\" class=\"fRating tRating\" >
                                                            </span>
                                                        </span>
                                                        <p class=\"text-left m-t-10\">".$list['description']."</p>
                                                        <div class=\"col-md-12 col-sm-12 col-xs-12 p-0\">
                                                            <a class=\"btn custom-btn\" href=\"".HTTP_ROOT.'home/salon-profile/'.$list['id']."\" target=\"_blank\" data-rel=\"".$list['id']."\"> View Profile </a>
                                                            <button class=\"btn custom-btn slct-btn\" type=\"button\" data-id=\"".$list['id'] ."\" data-rel=\"".$servicePrice."\"> Select </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>";

                        if(!empty($salonLists)) {
                            echo $this->sendAjaxResponse('success',$salonLists);
                            die;
                        } else {
                            echo $this->sendAjaxResponse('error','');
                            die;
                        }
                    }

            } else {
                echo $this->sendAjaxResponse('error','');
                die;
            }
        }
    }

    public function getSalonStaffList($id=null)
    {
        if($this->request->is('ajax')) {
            $this->loadModel('SalonCategoryPrices');
            $this->loadModel('UsersServices');
            $this->loadModel('Users');
            $data = $this->request->data;
            $serviceId = $data['serviceId'];
            $subServiceId = $data['subSurviceId'];

            $info = $this->SalonCategoryPrices->find()
                ->where(['SalonCategoryPrices.salon_id'=>$data['id']])
                ->contain(['Users','Users.UsersServices' => [
                    'conditions' => [
                        'service_id' => $serviceId,
                        'sub_service_id' => $subServiceId
                    ]
                ]])
                ->matching('StylistCategories',function ($qs) use ($serviceId, $subServiceId) {
                            return $qs
                                ->where(['SalonCategoryPrices.service_id' => $serviceId, 'SalonCategoryPrices.sub_service_id' => $subServiceId]);
                        })
                ->hydrate(false)
                ->toArray();
            $salonFlatDiscount = $this->Users->find()
                ->where(['Users.id'=> $data['id']])
                ->hydrate(false)
                ->first();


            $profileImage = HTTP_ROOT.'img/staticImage/default_user.png';
            $staffList = "";

            if(!empty($info)) {
                foreach ($info as $list) {

                    $flat_discount = $salonFlatDiscount['flat_discount'] ;
                    $flat_discount_amount = $list['price'] * ($flat_discount/100);
                    $discountedPrice = $list['price'] - $flat_discount_amount ;
                    $rating = $list['Users']['avg_rating'];
                    $duration = null;
                    if(isset($list['Users']['services']) && !empty($list['Users']['services'])) {
                        $service = $list['Users']['services'][0];
                        $duration = $service['duration'];
                    }
                    if($duration == null) {
                        throw new \Exception("Service duration not found.");
                    }
                    if( !empty($list['image']) && file_exists(WWW_ROOT.'img/salonMembers/'.$list['image'])) {
                        $profileImage = HTTP_ROOT.'img/salonMembers/'.$list['image'];
                    }
                    if($list['price'] == $discountedPrice)  {
                        $staffList .= "<div class=\"col-md-3 col-sm-6 col-xs-12\">
                                        <div class=\"servc-profile\">
                                            <img src=\"".$profileImage."\" class=\"img-responsive\" />
                                            <div class=\"m-0\">". $list['_matchingData']['StylistCategories']['name']."</div>
                                            <div class=\"m-b-10\" id=\"sPrice\">Stylist Charge : $".$list['price']."</div>
                                            <div> <strong>Service Duration:</strong> <big>$duration minutes</big> </div>
                                            <button class=\"btn custom-btn slct-btn2\" type=\"button\" data-rel=\"".$list['stylist_category_id']."\" data-discounted=\"".$list['price']."\" data-id=\"".$list['price']."\" data-duration=\"".$duration."\" > Select </button>

                                        </div>
                                    </div>";
                    //}
                } else {
                    $staffList .= "<div class=\"col-md-3 col-sm-6 col-xs-12\">
                                        <div class=\"servc-profile\">
                                            <img src=\"".$profileImage."\" class=\"img-responsive\" />
                                            <div class=\"m-0\">". $list['_matchingData']['StylistCategories']['name']."</div>
                                            <div class=\"m-b-10\" id=\"sPrice\">Stylist Charge :
                                                $".$discountedPrice."<s>$".$list['price']."</s></div>
                                            <div> <strong>Service Duration:</strong> <big>$duration minutes</big> </div>
                                            <button class=\"btn custom-btn slct-btn2\" type=\"button\" data-rel=\"".$list['stylist_category_id']."\" data-discounted=\"".$discountedPrice."\" data-id=\"".$discountedPrice."\" data-duration=\"".$duration."\" > Select </button>
                                        </div>
                                    </div>";
                }
            }
            } else {
                $staffList = "<div class=\"no-service-message\">Sorry!! No Stylist Found</div>";
            }

            echo $this->sendAjaxResponse('success',$staffList);
            die;
        } else {
            echo $this->sendAjaxResponse('error','');
            die;
        }
    }

    public function getStylist()
    {
        if($this->request->is('ajax')) {
            $this->loadModel('SalonCategoryPrices');
            $this->loadModel('Users');
            $this->loadModel('UsersServices');

            $data = $this->request->data;

             $info = $this->SalonCategoryPrices->find()
                ->where(['SalonCategoryPrices.stylist_category_id'=>$data['stylist'],
                    'SalonCategoryPrices.salon_id' => $data['salonId'],
                        'SalonCategoryPrices.service_id' => $data['serviceId'],
                            'SalonCategoryPrices.sub_service_id' => $data['slctSubServiceId']])
                ->contain(['Users','StylistCategories'])
                ->hydrate(false)
                ->first();

            $salonFlatDiscount = $this->Users->find()
                ->where(['Users.id' => $data['salonId']])
                ->hydrate(false)
                ->first();

            $serviceDuration = $this->UsersServices->find()
                ->where(['UsersServices.user_id' => $data['salonId'],
                    'UsersServices.service_id' => $data['serviceId'],
                    'UsersServices.sub_service_id' => $data['slctSubServiceId']])
                ->hydrate(false)
                ->first();

            $actual_price = $info['price'];
            $flat_discount = $salonFlatDiscount['flat_discount'] ;
            $flat_discount_amount = $actual_price * ($flat_discount/100);
            $discountedPrice = $actual_price - $flat_discount_amount ;

            $salonProfile = HTTP_ROOT.'img/staticImage/default_user.png';

            if( !empty($info['Users']['image']) && file_exists(WWW_ROOT.'img/profilePic/'.$info['Users']['image'])) {
                $salonProfile = HTTP_ROOT.'img/profilePic/'.$info['Users']['image'];
            }

            $rating = $info['Users']['avg_rating'];
                $sylistInfo = "<div class=\"col-md-4 col-sm-4 col-xs-12 p-l-0 text-center\">
                            <img src=\"".$salonProfile."\" class=\"img-responsive slservc-img1 customize-image\" />
                            <a class=\"btn custom-btn m-b-10\" href=\"".HTTP_ROOT.'home/salon-profile/'.$info['salon_id']."\" target=\"_blank\"> View Profile </a>
                        </div>

                        <div class=\"col-md-8 col-sm-8 col-xs-12 p-0\">
                            <div class=\"star-img\">
                                <h3>".$info['Users']['salon_name']."</h3>
                                <span class=\"star-img\">
                                    <span data-r=\"".$rating."\" class=\"fRating tRating\" ></span>
                                </span>
                            </div>

                            <div class=\"slservc-stylist\">
                                <!--<p class=\"text-left\"> <big> <strong> STYLIST CATEGORY
                                    </strong> </big> </p>-->
                                <div class=\"m-b-10\">
                                    ".$info['StylistCategories']['name']."

                                </div>
                                <div class=\"m-b-10\" id=\"sPrice\">Stylist Charge : $".$discountedPrice." </div>
                                <div class=\"m-b-10\" id=\"sduration\">Service Duration : ".$serviceDuration['duration']."minutes </div>

                            </div>
                        </div>" ;
                        //<img src=\"../img/staticImage/star.png\" class=\"img-responsive\" />
            echo $this->sendAjaxResponse('success',$sylistInfo);
            die;
        } else {
            echo $this->sendAjaxResponse('error','');
            die;
        }
    }

    /******* Sitemap and t&c***** by somu *****/

    public function termsAndCondition()
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','Terms and Conditions | StyleBrigade.com.au');
        $this->loadModel('Cms');
        $termsAndCondition = $this->Cms->find()
                ->hydrate(false)
                ->where(['id' => "11"])
                ->first();
        $this->set('terms',$termsAndCondition);
    }

    public function sitemap()
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','Sitemap | StyleBrigade.com.au');
    }

    /************ 12 Jan Start(Parul) *********/

    public function salonProfile($id)
    {
        $this->set('title','Salon Profile');
        $this->viewBuilder()->layout('public');
        if(!empty($id)) {
            $this->loadModel('Users');
            $this->loadModel('SalonStaffMembers');
            $this->loadModel('Ratings');
            $this->loadModel('Gallery');

            $info = $this->Users->find()
                ->contain(['UsersServices.Services','UsersServices.SubServices','ProfileImages'])
                ->where(['id'=>$id, 'admin_status'=>'Approved', 'status'=>'Active', 'user_type'=>'saloon'])
                ->hydrate(false)
                ->first();
            $rating = $this->Ratings->find()
                ->contain(['Users'])
                ->where(['Ratings.salon_id'=>$id])
                ->limit(3)
                ->hydrate(false)
                ->order(['Ratings.id desc'])
                ->toArray();

            $average_rating = $this->Users->find()
                ->where(['Users.id'=>$id])
                ->select(['Users.avg_rating','Users.reviews'])
                ->hydrate(false)
                ->first();

            if(empty($info)) {
                $this->Flash->error('Salon Not Found');
                return $this->redirect('/home');
            }

            $name = $info['salon_name'];
            $this->set('title',$name);
            $stylist = $this->SalonStaffMembers->find()
                        ->hydrate(false)
                        ->contain(['StaffMemberServices.Services'])
                        ->where(['SalonStaffMembers.salon_id' => $info['id']])
                        ->toArray();

            $imageGallery = $this->Gallery->find()
                ->where(['user_id' => $id, 'status' => 'Active'])
                ->hydrate(false)
                ->toArray();

            $this->set(compact('info','stylist','rating','average_rating','imageGallery'));
        } else {
            //$this->Flash->error('Salon Not Found');
            return $this->redirect('/home');
        }
    }

    public function stylistProfile($id)
    {
        $this->set('title','Stylist Profile');
        $this->viewBuilder()->layout('public');
        if(!empty($id)) {
            $this->loadModel('Users');
            $this->loadModel('SalonStaffMembers');
            $this->loadModel('Ratings');

            $stylist = $this->SalonStaffMembers->find()
                        ->hydrate(false)
                        ->contain(['StaffMemberServices.Services','StaffMemberServices.SubServices','Users','StylistCategories'])
                        ->where(['SalonStaffMembers.id' => $id ])
                        ->first();
            $rating = $this->Ratings->find()
                ->contain(['Users'])
                ->where(['Ratings.stylist_id'=>$id])
                ->limit(3)
                ->hydrate(false)
                ->order(['Ratings.id desc'])
                ->toArray();


            $average_rating = $this->Users->find()
                ->where(['Users.id'=>$id])
                ->select(['Users.avg_rating','Users.reviews'])
                ->hydrate(false)
                ->first();


            if(empty($stylist)) {
                return $this->redirect('/home');
            }
            $this->set('title',$stylist['first_name'].' '.$stylist['last_name']);
            $this->set(compact('stylist','rating','average_rating'));
        } else {
            return $this->redirect('/home');
        }
    }

    /******* 12 Jan End (Parul)  ******/

    public function getAvailability($id = null, $wStart = null, $wEnd = null, $serviceDuration = null)
    {
        if($id != null) {

            $this->loadModel('Availability');
            $this->loadModel('ActualAvailability');
            $this->loadModel('Bookings');

            $actualSlots = $this->ActualAvailability->find(
                    'list',
                    [
                        'keyField' => 'id', 'valueField' => 'slot'
                    ]
                )
                ->where([
                    'status' => 'Available',
                    'user_id' => $id,
                    'date >=' => $wStart,
                    'date <=' => $wEnd
                ]);

            $actualSlots = $actualSlots->toArray();

            if($actualSlots) {
                sort($actualSlots);
            }

            if($serviceDuration == null) {
                echo $this->sendAjaxResponse('slots', []); die;
            }

            $duration = $serviceDuration;

            $requiredSlots = 1;

            if($duration > 15) {
                $requiredSlots = ceil($duration / 15);
            }

            $oSlots = [];

            foreach ($actualSlots as $lSlot) {

                $checkCount = 1;
                $inProcess = [];

                for ($i = 1; $i <= $requiredSlots; $i++) {
                    if($i == 1) {
                        $inProcess[] = $lSlot;
                    } else {
                        $chkSlot = $inProcess[$i - 2];
                        $chkSlot = date('Y-m-d H:i:s', strtotime('+15 minutes' . $chkSlot));
                        $inProcess[] = $chkSlot;
                        if(in_array($chkSlot, $actualSlots)) {
                            $checkCount++;
                        }
                    }
                }

                if($checkCount >= $requiredSlots) {
                   $oSlots[] = date('Y-m-d H:00:00', strtotime($lSlot));
                }
            }

            $slots = [];

            if(!empty($oSlots)) {
              $slots = $this->Availability
                ->find()
                ->hydrate(false)
                ->where([
                    'user_id' => $id,
                    'date >=' => $wStart,
                    'date <=' => $wEnd,
                    'slot IN' => $oSlots
                ]);

                $slots->toArray();
            }

            echo $this->sendAjaxResponse('slots', $slots); die;
        }
    }

    /******* 19 Jan 2017 *******/

    function viewSalonRatings($id = null)
    {
        $this->viewBuilder()->layout("public");
        $this->loadModel('Users');
        $this->loadModel('Services');
        $this->loadModel('SalonStaffMembers');
        $this->loadModel('Ratings');

        $this->set('title','Salon Ratings');

        // $userInfo = $this->request->session()->read('Auth.User');
        $id = convert_uudecode(base64_decode($id));

        $user_info = $this->Users->find()
            ->where(['Users.id'=>$id])
            ->contain(['ProfileImages'])
            ->hydrate(false)
            ->first();
        $average_rating = $this->Users->find()
            ->where(['Users.id'=>$id])
            ->select(['Users.avg_rating','Users.reviews'])
            ->hydrate(false)
            ->first();

        $rating = $this->Ratings->find()
            ->contain(['Users'])
            ->where(['Ratings.salon_id'=>$id])
            ->hydrate(false)
            ->order(['Ratings.id desc'])
            ->toArray();

        $this->set(compact('rating','user_info','average_rating'));

    }

    function viewFreelancerRatings($id = null)
    {
        $this->viewBuilder()->layout("public");
        $this->loadModel('Users');
        $this->loadModel('Services');
        $this->loadModel('SalonStaffMembers');
        $this->loadModel('Ratings');

        $this->set('title','Freelancer Ratings');

        //$userInfo = $this->request->session()->read('Auth.User');
        $id = convert_uudecode(base64_decode($id));


        $user_info = $this->Users->find()
            ->contain(['ProfileImages'])
            ->where(['Users.id'=>$id])
            ->contain(['ProfileImages'])
            ->hydrate(false)
            ->first();

        $rating = $this->Ratings->find()
            ->contain(['Users'])
            ->where(['Ratings.freelancer_id'=>$id])
            ->hydrate(false)
            ->order(['Ratings.id desc'])
            ->toArray();

         $average_rating = $this->Users->find()
            ->where(['Users.id'=>$id])
            ->select(['Users.avg_rating','Users.reviews'])
            ->hydrate(false)
            ->first();

        $this->set(compact('rating','average_rating','user_info'));

    }

    function viewStylistRatings($id = null)
    {
        $this->viewBuilder()->layout("public");
        $this->loadModel('Users');
        $this->loadModel('Services');
        $this->loadModel('SalonStaffMembers');
        $this->loadModel('Ratings');

        $this->set('title','Stylist Ratings');

        //$userInfo = $this->request->session()->read('Auth.User');
        $id = convert_uudecode(base64_decode($id));

        $user_info = $this->SalonStaffMembers->find()
            ->where(['SalonStaffMembers.id'=>$id])
            ->hydrate(false)
            ->first();

        $rating = $this->Ratings->find()
            ->contain(['SalonStaffMembers','Users'])
            ->where(['Ratings.stylist_id'=>$id])
            ->hydrate(false)
            ->order(['Ratings.id desc'])
            ->toArray();
        $this->set(compact('rating','average_rating','user_info'));
    }


    /*********** Deepak Rathore *************/

    public function getAddresses()
    {
        if($this->request->is('ajax')) {
            $this->loadModel('AddressBooks');

            $userInfo = $this->request->session()->read('Auth.User');
            
            $userAdd = $this->AddressBooks->find()
                ->select(['id','address_title'])
                ->where(['user_id'=>$userInfo['id']])
                ->toArray();

            $addressList = "";
            foreach ($userAdd as $list) {
                $addressList .= "<div class=\"form-group col-md-12 col-sm-12 col-xs-12\">
                                                <input type=\"radio\" id=\"".$list['address_title']."\" name=\"Address\" class=\"cus-radio frlncr-rado\" value=\"".$list['id']."\" />
                                                <label for=\"".$list['address_title']."\">
                                                    <span>  </span> ".$list['address_title']."
                                                </label>
                                            </div>";
            }

            echo $this->sendAjaxResponse('success',$addressList);
            die;
        }
    }

    public function getCards()
    {
        if($this->request->is('ajax')) {
            $this->loadModel('SavedCards');
            $userInfo = $this->request->session()->read('Auth.User');

            $userCard = $this->SavedCards->find()
                ->select(['id','card_number'])
                ->where(['user_id'=>$userInfo['id']])
                ->toArray();

            $cardList = "";
            foreach ($userCard as $list) {
                $cardNumber = substr($list['card_number'], 0, 4) . str_repeat('X', strlen($list['card_number']) - 8) . substr($list['card_number'], -4);

                $cardList .= "<div class=\"saved-card\">
                                <input type=\"radio\" id='".$list["id"]."' value='".$list["id"]."' name=\"card\" class=\"cus-radio frlncr-rado\" />
                                <label for='".$list["id"]."'>
                                <span>  </span>".$cardNumber."
                                </label>
                            </div>" ;
            }

            echo $this->sendAjaxResponse('success',$cardList);
            die;
        }
    }

    public function bookingRequestHandler($user_id=null, $bId=null, $cust_id = null)
    {
        $this->loadModel('Users');
        $this->loadModel('Booking');

        $userId = convert_uudecode(base64_decode($user_id));
        $bookingId = convert_uudecode(base64_decode($bId));

        $userDetails = $this->Users->find()
            ->where(['id'=>$userId])
            ->hydrate(false)
            ->first();

        if(empty($userDetails)) {
            return $this->redirect(HTTP_ROOT.'home/login');
        } else {
            unset($userDetails['password']);
            unset($userDetails['decoded_password']);
            $this->Cookie->delete('Auth.User');
            $this->Cookie->delete('cookieData');
            $this->request->session()->delete('Auth.User');
            $this->request->session()->write('Auth.User',$userDetails);
            $userInfo = $this->request->session()->read('Auth.User');
            return $this->redirect(HTTP_ROOT."members/user-booking-detail/".$cust_id.'/'.$bId);
        }
    }

    /********** 20 Feb 2017 Start (Parul) *********/

    public function getSalonAddress($id = null)
    {
        if($this->request->is('ajax')) {
            $this->loadModel('AddressBooks');
            $this->loadModel('Users');

            $userAdd = $this->AddressBooks->find()
                ->contain(['Regions','Areas'])
                ->select(['AddressBooks.id','address_title','address1','address2','Regions.name','Areas.name'])
                ->where(['AddressBooks.user_id'=>$id,'AddressBooks.status' => 'Active'])
                ->toArray();

            $userAddress = $this->Users->find()
                ->contain(['Regions','Areas'])
                ->select(['Users.id','address1','address2','Regions.name','Areas.name'])
                ->where(['Users.id'=>$id])
                ->toArray();

            $addressList = "";
            if(isset($userAdd) && !empty($userAdd))
            {
                $info = array();
                foreach ($userAdd as $list) { //pr($list);die;
                    $addressList .= "<h5> <big> <center>
                                       ".$list['address1'].','.' '.$list['address2'].','.
                                            ' '.$list['area']['name'].','.' '.$list['region']['name']."
                                    </center> </big> </h5>";
                    $id = $list['id'];
                    $info['addressList'] = $addressList;
                    $info['id'] = $id;
                }

                echo $this->sendAjaxResponse('success',$info);
                die;
            }
            // elseif(isset($userAddress) && !empty($userAddress)) {
            //     foreach ($userAddress as $list) {
            //         $addressList .= "<h5> <big> <center>
            //                            ".$list['area']['name'].','.$list['region']['name'].','.
            //                                 $list['address1'].','.$list['address2']."
            //                         </center> </big> </h5>";
            //     }
            // }



            else {
                echo $this->sendAjaxResponse('success','');
                die;
            }
        }
    }

    /********** 20 Feb 2017 End (Parul) *********/

    /********** 24 Feb 2017 strat (Sam) *********/

    public function joinUs()
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','Join StyleBrigade | StyleBrigade.com.au');
        $this->loadModel('Cms');
        $joinus = $this->Cms->find()
                ->hydrate(false)
                ->where(['id' => "15"])
                ->first();
        $this->set('join',$joinus);
    }

   /********** 24 Feb 2017 start (Sam) *********/
   /******** 27 March 2017 start (Parul) *******/

    public function resendMail($uid = null,$email = null)
    {
        $this->viewBuilder()->layout("public");
        $this->set('title','SignUp');
        $this->request->session()->write('Auth.User');
        $this->loadModel('Users');
        $uid = convert_uudecode(base64_decode($uid));
            $data = $this->Users->find()
                        ->where(['id' => $uid])
                        ->hydrate(false)
                        ->first();

            $info = $this->Users->ResendMail($uid,$email,$data);

            if($info = 'success')
            {
                $this->Flash->info('We have sent you an email. Please click on the verification link.');
                $this->redirect(HTTP_ROOT.'home');
            }

    }

   /******** 27 March 2017 end (Parul) *******/


   public function registrationSuccess()
   {
        $this->viewBuilder()->layout('public');
        $this->set('title', "Registration Successful");

   }

   /******** 20 April Start (Parul) ********/

   public function getAvailableSlots($id = null, $date = null, $Time = null, $duration = null)
    {
        if($id != null) {

            $this->loadModel('ActualAvailability');
            $this->loadModel('SalonCategoryPrices');
            $this->loadModel('Bookings');
            $bookingData = $this->request->data;
            $rQuery = $this->request->query;

            if(isset($rQuery['type']) && $rQuery['type'] == 'freelancer') {
                $rSlots = '';
                $serviceDuration = $duration;
                 // get end time to select all available slots for that hour
                $endTime = date('H:i:s', strtotime($Time . '+ 1 hour'));
                $slots = $this->ActualAvailability
                    ->find()
                    ->hydrate(false)
                    ->where([
                        'user_id' => $id,
                        'date' => $date,
                        'end_time <=' => $endTime ,
                        'start_time >=' => $Time,
                        'status' => 'Available'
                    ])
                    ->order(['start_time'=> 'ASC'])
                    ->toArray();

                $requiredSlots = 1;

                if($serviceDuration > 15) {
                    $requiredSlots = ceil($serviceDuration / 15);
                }


                foreach ((array) $slots as $slot) {

                    $bookingCount = $this->Bookings->find()
                        ->where([
                            'freelancer_id' => $id,
                            'booking_date' => $slot['date'],
                            'booking_time <=' => $slot['start_time'],
                            'booking_end_time >' => $slot['start_time']
                        ])
                        ->count();

                    $chkValid = true;

                    if($bookingCount >= 1) {
                        $chkValid = false;
                    }

                    $slotsShouldBeAvailable = [];
                    $slotsShouldBeAvailable[0] = $slot['slot'];
                    // -1 for excluding main slot
                    for($i = 0; $i < $requiredSlots; $i++) {
                        if($i == 0) {
                            $slotsShouldBeAvailable[$i] = $slot['slot'];
                        } else {
                            $slotsShouldBeAvailable[$i] = date('Y-m-d H:i:s', strtotime('+'.'15'.'minutes'.' '.$slotsShouldBeAvailable[$i-1]));
                        }
                    }

                    $count = $this->ActualAvailability
                        ->find()
                        ->where([
                            'user_id' => $id,
                            'slot IN' => $slotsShouldBeAvailable,
                            'status' => 'Available'
                        ])
                        ->count();
                    if($count == count($slotsShouldBeAvailable) && $chkValid) {
                        $newDateTime = date('h:i A', strtotime($slot['start_time']));

                        $rSlots .= "<div class = \"timeshowbox\" data-rel=\"".$slot['date']."\"
                                    rel=\"".$slot['start_time']."\">
                                    <p class = \"show\">".$newDateTime."</p>
                                </div>";

                    }

                }


                if(empty($rSlots)) {
                    $rSlots = "<div class=\"no-service-message\">Sorry, no booking slots available at this time.</div>";
                }



                echo $this->sendAjaxResponse('success', $rSlots, $serviceDuration); die;
            }

            if(isset($rQuery['type']) && $rQuery['type'] == 'salon') {
                $serviceDuration = $duration;
                // get end time to select all available slots for that hour
                $endTime = date('H:i:s', strtotime($Time . '+ 1 hour'));
                $end_time = date('H:i:s', strtotime('+'.$serviceDuration.'minutes'.' '.$Time));

                $slots = $this->ActualAvailability
                    ->find()
                    ->hydrate(false)
                    ->where([
                        'user_id' => $id,
                        'date' => $date,
                        'end_time <=' => $endTime ,
                        'start_time >=' => $Time,
                        'status' => 'Available'
                    ])
                    ->order(['start_time'=> 'ASC'])
                    ->toArray();

                $requiredSlots = 1;
                $stylistId = $bookingData['Booking']['stylist'];
                /*
                * Duration is greater then 15 then calculate total
                * slots required otherwise slots required is 1
                */

                if($serviceDuration > 15) {
                    $requiredSlots = ceil($serviceDuration/15);
                }

                $info = '';

                foreach ((array) $slots as $slot) {

                    $bookingCount = $this->Bookings->find()
                        ->where([
                            'salon_id' => $id,
                            'salon_staff_member_id' => $stylistId,
                            'booking_date' => $slot['date'],
                            'booking_time <=' => $slot['start_time'],
                            'booking_end_time >' => $slot['start_time']
                        ])
                        ->count();

                    $chkValid = true;

                    if($bookingCount >= 20) {
                        $chkValid = false;
                    }

                    $slotsShouldBeAvailable = [];
                    $slotsShouldBeAvailable[0] = $slot['slot'];
                    // -1 for excluding main slot
                    for($i = 0; $i < $requiredSlots; $i++) {
                        if($i == 0) {
                            $slotsShouldBeAvailable[$i] = $slot['slot'];
                        } else {
                            $slotsShouldBeAvailable[$i] = date('Y-m-d H:i:s', strtotime('+'.'15'.'minutes'.' '.$slotsShouldBeAvailable[$i-1]));
                        }

                    }

                    $count = $this->ActualAvailability
                        ->find()
                        ->where([
                            'user_id' => $id,
                            'slot IN' => $slotsShouldBeAvailable,
                            'status' => 'Available'
                        ])
                        ->count();
                    if($count == count($slotsShouldBeAvailable) && $chkValid) {
                        $newDateTime = date('h:i A', strtotime($slot['start_time']));

                        $info .= "<div class = \"timeshowbox\" data-rel=\"".$slot['date']."\"
                                    rel=\"".$slot['start_time']."\">
                                    <p class = \"show\">".$newDateTime."</p>
                                </div>";

                    }

                }

                if(empty($info)) {
                    echo $this->sendAjaxResponse('error'); die;
                }

                echo $this->sendAjaxResponse('success', $info, $serviceDuration); die;
            }



            throw new \Exception('Invalid Request Parameters.');

        }
    }

    public function getSlots($id = null,$date = null,$Time = null,$duration = null)
    {
        if($id != null) {
            $this->loadModel('ActualAvailability');
            $duration = $duration.'minutes';
            $end_time = date('H:i:s', strtotime('+'. $duration.' '.$Time));
            $slots = $this->ActualAvailability
                ->find()
                ->hydrate(false)
                ->where([
                    'user_id' => $id,
                    'date' => $date,
                    'start_time >= ' => $Time,
                    'end_time <=' => $end_time
                ])
                ->toArray();

            if(!empty($slots)) {
                echo $this->sendAjaxResponse('success');
                die;
            } else {
                echo $this->sendAjaxResponse('error');
                die;
            }

        }
    }

    public function getSalonCalendarAvailableSlots($date = null,$Time = null,$serviceId = null,
        $subServiceId = null)
    {
        if($date != null) {
            $slotEndTime = date('H:i:s', strtotime('+' . '1 hour' . $Time));
            $this->loadModel('ActualAvailability');
            $salonList = $this->ActualAvailability->find()
                ->contain(['Users'])
                ->distinct('ActualAvailability.user_id')
                ->where([
                    'Users.user_type'=>'saloon',
                    'ActualAvailability.date'=>$date,
                    'ActualAvailability.start_time >='=>$Time,
                    'ActualAvailability.end_time <='=>$slotEndTime
                ])
                ->hydrate(false)
                ->toArray();

            foreach($salonList as $list)
            {
                $this->loadModel('UsersServices');
                $serviceDuration = $this->UsersServices->find()
                    ->where(['user_id' => $list['user_id'],
                        'sub_service_id'=> $subServiceId,
                        'service_id' => $serviceId])
                    ->hydrate(false)
                    ->first();
                $duration = $serviceDuration['duration'];
                $end_time = date('H:i:s', strtotime('+'.$duration.'minutes'.$Time));

                $slots = $this->ActualAvailability
                    ->find()
                    ->hydrate(false)
                    ->where([
                        'user_id' => $list['user_id'],
                        'date' => $date,
                        'end_time <=' => $end_time ,
                        'start_time >=' => $Time,
                        'status' => 'Available'
                    ])
                    ->first();
            }



            $info = '';
            if(!empty($slots)) {

                for ($i=0; $i < 4; $i++) {
                    //foreach ($Times as $Time) {
                        if($i==0){
                            $startTime = date('H:i:s', strtotime($Time));
                            $newDateTime = date('h:i a', strtotime($Time));
                        }else{
                            $startTime = date('H:i:s', strtotime('+15 minutes'.$startTime));
                            $newDateTime = date('h:i a', strtotime('+15 minutes'.$newDateTime));
                        }

                        $info .= "<div class = \"timeshowbox\" data-rel=\"".$slots['date']."\"rel=\"".$startTime."\">
                                        <p class = \"show\">".$newDateTime."</p>
                                    </div>";
                    //}

                }

            } else {
                $info   .=  "<div class='no-service-message'>
                                Sorry, No slot available.
                           </div>";
            }

            echo $this->sendAjaxResponse('success',$info);
            die;
        }
    }

    public function getFreelancerCalendarAvailableSlots($date = null, $Time = null, $duration = null)
    {
        if($date != null) {

            $this->loadModel('ActualAvailability');
            $this->loadModel('Users');
            $data = $this->request->data;

            $duration = $data['Booking']['duration'];
            $serviceId = $data['Booking']['slctCategoryId'];
            $subServiceid = $data['Booking']['slctSubServiceId'];
            $slotEndTime = date('Y-m-d H:i:s', strtotime('+' . '1' . 'hour' . $date . ' ' . $Time));
            $info = '';
            $requiredSlots = 1;

            if($duration > 15) {
                $requiredSlots = ceil($duration / 15);
            }

            $allslots = [];

            for($oI = 0; $oI < 4; $oI++) {
                if($oI == 0) {
                    $allslots[$oI] = $date . ' ' . $Time;
                } else {
                    $allslots[$oI] = date('Y-m-d H:i:s', strtotime('+'.'15'.'minutes'.' '.$allslots[$oI-1]));
                }
            }

            $outSlots = [];

            //prxx($allslots);

            foreach ($allslots as $slot) {

                $slotsShouldBeAvailable = [];

                for($i = 0; $i < $requiredSlots; $i++) {
                    if($i == 0) {
                        $slotsShouldBeAvailable[$i] = $slot;
                    } else {
                        $slotsShouldBeAvailable[$i] = date('Y-m-d H:i:s', strtotime('+'.'15'.'minutes'.' '.$slotsShouldBeAvailable[$i-1]));
                    }
                }

                //prxx($slotsShouldBeAvailable);

                $users = $this->Users->find()
                    ->hydrate(false)
                    //->distinct(['ActualAvailability.id'])
                    ->where([
                        'Users.status' => 'active',
                        'Users.user_type' => 'freelancer',
                    ])
                    ->matching('UsersServices', function($q) use ($serviceId, $subServiceid) {
                        return $q->where([
                            'UsersServices.service_id' => $serviceId,
                            'UsersServices.sub_service_id' => $subServiceid,
                        ]);
                    })
                    ->matching('ActualAvailability', function($q) use ($slotsShouldBeAvailable) {
                        return $q->where([
                            'ActualAvailability.slot IN' => $slotsShouldBeAvailable,
                            'ActualAvailability.status' => 'Available'
                        ]);
                    });

                //prxx($users->count());

                if($users->count() >= $requiredSlots) {
                    $stime = date('h:i a', strtotime($slot));
                    $time = date('h:i:s', strtotime($slot));
                    $date = date('Y-m-d', strtotime($slot));

                    $info .= "<div class = \"timeshowbox\" data-rel=\"".$date."\"rel=\"".$time."\">
                                <p class = \"show\">".$stime."</p>
                            </div>";
                }


            }

            if(empty($info)) {
                $info .= "<div class='no-service-message'>Sorry, No slot available.</div>";
                echo $this->sendAjaxResponse('error', $info);
                die;
            }

            echo $this->sendAjaxResponse('success', $info);
            die;
        }
    }

    public function getFreelancerCalSlots($date = null,$Time = null,$duration = null)
    {

        if($date != null) {
            $this->loadModel('Availability');
            $duration = $duration.'minutes';
            $end_time = date('H:i:s', strtotime('+'. $duration.' '.$Time));
            //pr($end_time);die;
            $slots = $this->Availability
                ->find()
                ->hydrate(false)
                ->where([
                    'date' => $date,
                    'end_time ' => $end_time
                ])
                ->toArray();

            if(!empty($slots)) {
                echo $this->sendAjaxResponse('success');
                die;
            } else {
                echo $this->sendAjaxResponse('error');
                die;
            }

        }
    }

    public function getSlotsForSalon($id = null,$stylistId = null,$date = null,$Time = null,$duration = null)
    {
        echo $this->sendAjaxResponse('success'); die;
        if($id != null) {
            $this->loadModel('ActualAvailability');
            $this->loadModel('Bookings');
            $count = $this->Bookings->find()
                ->where(['Bookings.salon_id' => $id,'Bookings.salon_staff_member_id' => $stylistId,
                    'Bookings.booking_date' => $date,'Bookings.booking_time' => $Time])
                ->count();

            $duration = $duration.'minutes';
            $end_time = date('H:i:s', strtotime('+'. $duration.' '.$Time));

            if($count <= 0) {
                $slots = $this->ActualAvailability
                    ->find()
                    ->hydrate(false)
                    ->where([
                        'user_id' => $id,
                        'date' => $date,
                        'start_time >=' => $Time,
                        'end_time <=' => $end_time
                    ])
                    ->toArray();

                if(!empty($slots)) {
                    echo $this->sendAjaxResponse('success');
                    die;
                } else {
                    echo $this->sendAjaxResponse('error');
                    die;
                }
            } else {
                $slots = $this->ActualAvailability
                    ->find()
                    ->hydrate(false)
                    ->where([
                        'user_id' => $id,
                        'date' => $date,
                        'start_time >= ' => $Time,
                        'end_time <=' => $end_time
                    ])
                    ->toArray();
                if(!empty($slots)) {
                    echo $this->sendAjaxResponse('success1');
                    die;
                } else {
                    echo $this->sendAjaxResponse('error');
                    die;
                }
            }
                // if(!empty($slots)) {
                //     echo $this->sendAjaxResponse('success1');
                //     die;
                // } else {
                //     echo $this->sendAjaxResponse('error');
                //     die;
                // }
        }

            // if(!empty($slots)) {
            //     echo $this->sendAjaxResponse('success');
            //     die;
            // } else {
            //     echo $this->sendAjaxResponse('error');
            //     die;
            // }
        //}
    }

    // public function getSalonCalSlots($id = null,$date = null,$Time = null,
    //     $serviceId = null,$subServiceId = null)
    // {
    //     if($date != null) {
    //         $this->loadModel('Availability');
    //         $this->loadModel('UsersServices');
    //         $serviceDuration = $this->UsersServices->find()
    //             ->where(['UsersServices.user_id' => $id,
    //                 'service_id'=> $serviceId,
    //                 'sub_service_id'=> $subServiceId])
    //             ->hydrate(false)
    //             ->first();

    //         $duration = $serviceDuration['duration'];
    //         $duration = $duration.'minutes';
    //         $end_time = date('H:i:s', strtotime('+'. $duration.' '.$Time));

    //         $slots = $this->Availability
    //             ->find()
    //             ->hydrate(false)
    //             ->where([
    //                 'date' => $date,
    //                 'end_time ' => $end_time
    //             ])
    //             ->toArray();

    //         if(!empty($slots)) {
    //             echo $this->sendAjaxResponse('success');
    //             die;
    //         } else {
    //             echo $this->sendAjaxResponse('error');
    //             die;
    //         }

    //     }
    // }

    public function paypalPaymentDone() {
        $args = func_get_args();
        $q = $this->request->query;
        $data = array();
        $data['args'] = $args;
        $data['data'] = $_POST;
        $data['q'] = $q;
        $data['query'] = $_GET;
        $postdata = file_get_contents("php://input");
        $postdata = $this->getRealPOST($postdata); // getting post data sometimes it can be error in normal mode
        $data['data'] = $postdata;
        $this->loadModel('Users');
        $this->loadModel('MemberPayments');
        $this->loadModel('Bookings');

        if(!empty($args)) { // if it is returning from the payapl

            $flag = 0;
            $token = $args[1];
            $token = $this->Utility->decode($token);
            $token  = explode('/', $token); //booking id/payment id
            $booking = $token[0];
            $payment = $token[1];

            $paymentInfo = $this->MemberPayments->find()
                ->where(
                    [
                        'id' => $payment,
                        'status' => 'Pending'
                    ]
                )
                ->first();

            if($paymentInfo != null) {
                $updateSession = [];
                $updateSession['status'] = 'Failed';

                $testing = true; // for testing at local

                if( isset($data['data']['payer_email']) && isset($data['data']['txn_id']) && ($data['data']['payment_status'] == 'Completed') && ($paymentInfo['amount_paid'] == $data['data']['payment_gross']) || $testing) {

                    $sessions = $this->MemberPayments->find()
                        ->where(['id' => $payment])
                        ->hydrate(false)
                        ->first();

                    $updateData = $this->MemberPayments->query();
                    $updateData->update()
                        ->set(['status' => 'Success'])
                        ->where(['id' => $payment])
                        ->execute();

                    $this->loadModel('Users');
                    $userInfo = $this->Users->find()
                        ->where(['Users.id' => $sessions['user_id']])
                        ->hydrate(false)
                        ->first();

                    $payment_left = $userInfo['due_payment'] - $sessions['amount_paid'];

                    $query = $this->Users->query();
                    $query->update()
                        ->set(['due_payment' => $payment_left])
                        ->where(['id' => $sessions['user_id']])
                        ->execute();

                    $this->Flash->success('Payment Successful');
                    if($sessions['user_type'] == 'freelancer') {
                        return $this->redirect(HTTP_ROOT.'admin/users/freelancerManagement');
                    } elseif($sessions['user_type'] == 'saloon') {
                         return $this->redirect(HTTP_ROOT.'admin/users/saloonManagement');
                    }

                    exit;

                } else {
                   $this->sendSessionPaymentFailsEmail($booking);
                }

                $this->Flash->frontendMessageError('Payment No successful.');
                (new \Log\Controller\LogsController)->write('Session Payment Inside Error', array('session' => $paymentInfo, 'data' => $data));
               return $this->redirect(HTTP_ROOT.'admin/users/dashboard');
                exit;


            } else {

                if(!empty($args) &&  $args[0] == 'payapl') { // it is payapl notification for the payment
                    echo $this->sendAjaxResponse('error', [], 1); die;
                }


                if(!empty($args) &&  $args[0] == 'return') { // if user is visiting after after the Pay pal notification
                    $paymentInfo = $this->SessionPayments->find()
                    ->where(
                        [
                            'id' => $payment,
                            'status' => 'Success'
                        ]
                    )
                    ->first();

                    if($paymentInfo != null) {
                        $this->Flash->frontendMessageSuccess('Payment Successful');
                        return $this->redirect(HTTP_ROOT . 'home/payment-success');
                        exit;
                    }

                } else {

                    $this->Flash->frontendMessageError('Some error please try again!');
                    return $this->redirect(HTTP_ROOT);
                    exit;
                }
            }
        }
    }

    public function comingSoon()
    {
        $this->viewBuilder()->layout('public');
        $this->set('title', 'Coming Soon | stylebrigade.co');
    }

    public function gitTestFunction() 
    {
	echo "asdf";
    }

}
