<?php
/***
Template name:Front page tamplate



***/
get_header(); ?>

<!-- nav bar end -->

<!-- banner section start --> 
<section id="banner">
	<div class="container">
		<div class="row">
			<div class="banner_txt col-lg-6 col-lg-push-6 col-md-6 col-md-push-6 col-sm-push-0 col-sm-12 ">
<h3>Create your <span>Path</span> with</h3>
<h1>Vendor Portal</h1>
</div>
		</div>
	</div>
	<div class="buttom_part">
		<div class="container">
			<div class="row">
				<div class="col-md-offset-1 col-md-10">
				<?php if(Noo_Member::is_logged_in()):?>
					<?php else:?>
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 full">
					
						<li class="menu-item nav-item-member-profile  align-center">
							<a class="blue_btn member-links member-login-link" href="#"><i class="fa fa-sign-in"></i>&nbsp;Login for Recruiter</a>
						</li>
						
						
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 full">
						<li class="menu-item nav-item-member-profile  align-center">
							<a class="green_btn member-links member-login-link" href="#"><i class="fa fa-sign-in"></i>&nbsp;Login for Employer</a>
						</li>

					</div>
				</div>
				<?php endif;?>
			</div>
		</div>
	</div>
</section>
<!-- banner section end --> 

<!-- content section start --> 
<section id="content">
	<div class="container">
		<div class="text-center home-text">
			<?php dynamic_sidebar("Home_page");?>
		</div>
	</div>
	<img src="<?php bloginfo("template_url");?>/assets/images/shape_top.png" alt="shape-img" class="top_shape">
	<img src="<?php bloginfo("template_url");?>/assets/images/shape_bottom.png" alt="shape-img" class="bottom_shape">
</section>
<!-- content section end --> 

<!-- vedio section start --> 
<section id="video">
	<div class="container">
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 video_img">
				<img src="<?php bloginfo("template_url");?>/assets/images/video1.jpg" alt="recruiter-video-img">
				<a href="https://14-lvl3-pdl.vimeocdn.com/01/4864/0/24323037/52523240.mp4?expires=1460530946&token=0f07e202722187bebe55f" class="fancybox-youtube video_txt">
					<h4>DEMO FOR </h4>
					<h2>RECRUITERS</h2>
				</a>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 video_img">
				<img src="<?php bloginfo("template_url");?>/assets/images/vedio2.jpg" alt="recruiter-video-img">
				<a href="https://07-lvl3-pdl.vimeocdn.com/01/4864/0/24324134/52527882.mp4?expires=1460530880&token=0e95dbc4ffccf220d4239" class="fancybox-youtube video_txt2">
					<h4>DEMO FOR </h4>
					<h2>EMPLOYER</h2>
				</a>
			</div>
		</div>
	</div>
</section>
<!-- vedio section end --> 



<!-- jQuery (necessary for Bootstrap's JavaScript plugins) --> 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script> 
<!-- bootstrap js --> 
<script src="<?php bloginfo("template_url");?>/assets/js/bootstrap.min.js"></script> 
<script>
	window.requestAnimFrame = (function(callback) {
		return window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.oRequestAnimationFrame || window.msRequestAnimationFrame ||
		function(callback) {
			window.setTimeout(callback, 1000 / 60);
		};
	})();

	var requestId, jolttime;

	var c = document.getElementById('canv');
	var $ = c.getContext('2d');

		var s = 18; //grid square size
		var mv = 10; //moving areas
		var sp = 1; //move speed
		var clm = 23; //columns
		var rw = 10; //rows
		var x = []; //x array
		var y = []; //y array
		var X = []; //starting X array
		var Y = []; //starting Y array

		c.width  = c.offsetWidth;
		c.height = c.offsetHeight;

		for (var i = 0; i < clm * rw; i++) {
			x[i] = ((i % clm) - 0.5) * s;
			y[i] = (Math.floor(i / clm) - 0.5) * s;
			X[i] = x[i];
			Y[i] = y[i];
		}
		var t = 0;

		function jolt() {
			$.fillRect(0, 0, c.width, c.height);

			for (var i = 0; i < clm * rw; i++) {
				if (i % clm != clm - 1 && i < clm * (rw - 1) - 1) {
					$.fillStyle = "hsla(0,0,0,1)";
					$.strokeStyle = "#95D384";
					$.lineWidth = 1;
					$.beginPath();
					$.moveTo(x[i], y[i]);
					$.lineTo(x[i + 1], y[i + 1]);
					$.lineTo(x[i + clm + 1], y[i + clm + 1]);
					$.lineTo(x[i + clm], y[i + clm]);
					$.closePath();
					$.stroke();
					$.fill();
				}
			}
			for (var i = 0; i < rw * clm; i++) {
				if ((x[i] < X[i] + mv) && (x[i] > X[i] - mv) && (y[i] < Y[i] + mv) && (y[i] > Y[i] - mv)) {
					x[i] = x[i] + Math.floor(Math.random() * (sp * 2 + 1)) - sp;
					y[i] = y[i] + Math.floor(Math.random() * (sp * 2 + 1)) - sp;
				} else if (x[i] >= X[i] + mv) {
					x[i] = x[i] - sp;
				} else if (x[i] <= X[i] - mv) {
					x[i] = x[i] + sp;
				} else if (y[i] >= Y[i] + mv) {
					y[i] = y[i] - sp;
				} else if (y[i] <= Y[i] + mv) {
					y[i] = y[i] + sp;
				}
			}
			//controls time of electric shake> when counter equals 0, it will reset for 5s then start again.
			if (t % c.width == 0) {
				jolttime = setTimeout('jolt()', 5);
				t++;
			} else {
				jolttime = setTimeout('jolt()', 5);
				t++;
			}
		}

		function start() {
			if (!requestId) {
				requestId = window.requestAnimFrame(jolt);
			}
		}

		function stop() {
			if (requestId) {
				clearTimeout(jolttime);
				window.cancelAnimationFrame(requestId);
				requestId = undefined;
			}
		}

		document.querySelector('a.link--asiri').addEventListener('mouseenter', start);
		document.querySelector('a.link--asiri').addEventListener('mouseleave', stop);
	</script>




	<?php get_footer();?>