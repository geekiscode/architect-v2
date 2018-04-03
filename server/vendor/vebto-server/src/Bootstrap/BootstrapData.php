<?php namespace Vebto\Bootstrap;

use Vebto\Billing\Gateways\Paypal\PaypalGateway;
use App\User;
use Vebto\Groups\Group;
use Illuminate\Http\Request;
use Vebto\Localizations\LocalizationsRepository;
use Vebto\Settings\Settings;
use Vebto\Localizations\Localization;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BootstrapData
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Localization
     */
    private $localizationRepository;

    /**
     * @var Group
     */
    private $groups;

    /**
     * BootstrapData constructor.
     *
     * @param Settings $settings
     * @param Request $request
     * @param Localization $localization
     * @param Group $groups
     */
    public function __construct(
        Settings $settings,
        Request $request,
        LocalizationsRepository $localizationsRepository,
        Group $groups
    )
    {
        $this->groups = $groups;
        $this->request = $request;
        $this->settings = $settings;
        $this->localizationRepository = $localizationsRepository;
    }

    /**
     * Get data needed to bootstrap the application.
     *
     * @return string
     */
    public function get()
    {
        $bootstrap = [];
        $bootstrap['settings'] = $this->settings->all();
        $bootstrap['settings']['base_url'] = url('');
        $bootstrap['settings']['version'] = config('vebto.site.version');
        $bootstrap['csrf_token'] = csrf_token();
        $bootstrap['guests_group'] = $this->groups->where('guests', 1)->first();
        $bootstrap['i18n'] = $this->getLocalizationsData() ?: null;
        $bootstrap['user'] = $this->getCurrentUser();

        if ($bootstrap['user']) {
            $bootstrap['user'] = $bootstrap['user']->toArray();
        }

        return base64_encode(json_encode($bootstrap));
    }

    /**
     * Load current user and his groups.
     */
    private function getCurrentUser()
    {
        $user = $this->request->user();

        if ($user && ! $user->relationLoaded('groups')) {
            $user->load('groups');
        }

        return $user;
    }

    /**
     * Get currently selected i18n language.
     *
     * @return Localization
     */
    private function getLocalizationsData()
    {
        if ( ! $this->settings->get('i18n.enable')) return null;

        //get user selected or default language
        $userLang = $this->request->user() ? $this->request->user()->language : null;

        if ( ! $userLang) {
            $userLang = $this->settings->get('i18n.default_localization');
        }

        if ($userLang) {
            return $this->localizationRepository->getByName($userLang);
        }
    }
}
