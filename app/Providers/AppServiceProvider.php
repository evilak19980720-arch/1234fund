<?php

namespace App\Providers;

use App\Models\Activity;
use App\Admin\Models\Activity as ActivityAdmin;
use App\Models\Article;
use App\Models\Bank;
use App\Models\Country;
use App\Models\Banner;
use App\Models\Card;
use App\Models\Configuration;
use App\Models\Currency;
use App\Models\Pixcel;
use App\Models\Customer;
use App\Models\Interest;
use App\Models\InterestLog;
use App\Models\InterestRevenue;
use App\Models\Issue;
use App\Models\Manual;
use App\Models\Notice;
use App\Models\Pet;
use App\Models\Rule;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\Tran;
use App\Models\Type;
use App\Models\Version;
use App\Models\Vip;
use App\Models\Locale;
use App\Observers\ActivityObserver;
use App\Observers\ArticleObserver;
use App\Observers\BankObserver;
use App\Observers\CountriesObserver;
use App\Observers\PixcelObserver;
use App\Observers\BannerObserver;
use App\Observers\CardObserver;
use App\Observers\ConfigurationObserver;
use App\Observers\CurrenciesObserver;
use App\Observers\CustomerObserver;
use App\Observers\InterestLogObserver;
use App\Observers\InterestRevenueObserver;
use App\Observers\IssueObserver;
use App\Observers\LocalesObserver;
use App\Observers\ManualObserver;
use App\Observers\NoticeObserver;
use App\Observers\PetObserver;
use App\Observers\RuleObserver;
use App\Observers\TaskLogObserver;
use App\Observers\TaskObserver;
use App\Observers\TypeObserver;
use App\Observers\VersionObserver;
use App\Observers\VipObserver;
use App\Observers\InterestObserver;
use App\Observers\TranObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Observers\PaymentThirdPayObserver;
use App\Observers\PaymentThirdCodeObserver;
use App\Models\PaymentThirdCode;
use App\Models\Payment;
use App\Observers\AgencyContestUserObserver;
use App\Models\AgencyContestUser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        // Laravel 8 起分页默认改为 Tailwind 视图，保持原来的 Bootstrap 分页样式
        Paginator::useBootstrap();
        Pet::observe(PetObserver::class);
        Bank::observe(BankObserver::class);
        Country::observe(CountriesObserver::class);
        Pixcel::observe(PixcelObserver::class);
        Currency::observe(CurrenciesObserver::class);
        Locale::observe(LocalesObserver::class);
        Tran::observe(TranObserver::class);
        Vip::observe(VipObserver::class);
        Type::observe(TypeObserver::class);
        Task::observe(TaskObserver::class);
        TaskLog::observe(TaskLogObserver::class);
        Rule::observe(RuleObserver::class);
        Notice::observe(NoticeObserver::class);
        Banner::observe(BannerObserver::class);
        Configuration::observe(ConfigurationObserver::class);
        Activity::observe(ActivityObserver::class);
        ActivityAdmin::observe(ActivityObserver::class);
        Interest::observe(InterestObserver::class);
        InterestLog::observe(InterestLogObserver::class);
        InterestRevenue::observe(InterestRevenueObserver::class);
        Manual::observe(ManualObserver::class);
        Customer::observe(CustomerObserver::class);
        Article::observe(ArticleObserver::class);
        Version::observe(VersionObserver::class);
        Card::observe(CardObserver::class);
        Issue::observe(IssueObserver::class);
        PaymentThirdCode::observe(PaymentThirdCodeObserver::class);
        Payment::observe(PaymentThirdPayObserver::class);
        AgencyContestUser::observe(AgencyContestUserObserver::class);
    }
}
