<i18n>
    {
        "en": { 
            "appName" :"Manpower request",
            "accessDenied": "You do not have permission to access this application.",
            "User manual": "User manual",
            "Go home": "Go home",
            "Hi": "Hi",
            "Main page - Admin": "Main page - Admin",
            "Main page - User": "Main page - User"
        },
        "cn": {
            "appName" :"人員增補申請",
            "accessDenied": "您无权访问此应用程序。",
            "User manual": "用户手册",
            "Go home": "回家",
            "Hi": "嗨",
            "Main page - Admin": "主頁-管理者",
            "Main page - User": "主頁-使用者"
        },
        "vi": {
            "appName" :"Đơn xin bổ sung công nhân nhân viên",
            "accessDenied": "Bạn không được phép truy cập vào ứng dụng này.",
            "User manual": "Hướng dẫn sử dụng",
            "Go home": "Trang chủ",
            "Hi": "Xin chào",
            "Main page - Admin": "Trang chính - Quản trị viên",
            "Main page - User": "Trang chính - Người dùng"
        }
    }
</i18n>
<template>
    <div>
        <v-app v-if="loaded" dark>
            <v-navigation-drawer temporary color="#01a6be" dark v-model="drawer" :mini-variant="miniVariant"
            :clipped="clipped" fixed app>
            <v-list>
                <v-list-item v-for="(item, i) in items" :key="i" :to="item.to" :href="item.href" router exact>
                    <v-list-item-action>
                        <v-icon>{{ item.icon }}</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                        <v-list-item-title v-text="$t(item.title)" />
                    </v-list-item-content>
                </v-list-item>
            </v-list>
        </v-navigation-drawer>
        <v-app-bar class="noPrint" dense color="#01a6be" dark :clipped-left="clipped" fixed app>
            <v-app-bar-nav-icon @click.stop="drawer = !drawer" />
            <v-toolbar-title v-text="$t('appName')" />
            <v-chip class="ma-2" color="white" label small outlined @click:close="chip4 = false">
                V.1.4 [Test - demo]
            </v-chip>
            <v-spacer />
            <v-btn @click="locale('vi')" text>VI</v-btn>
            <v-btn @click="locale('en')" text>EN</v-btn>
            <v-btn @click="locale('cn')" text class="mr-10">中文</v-btn>
            
            <label class="mr-5">
                {{ this.$session.get("mpr")
                ? `${$t("Hi")}: ${this.$session.get("mpr").name} -
                    ${this.$session.get("mpr").dept}`
                : ""
            }}
        </label>
    </v-app-bar>
    <v-main>
        <v-container fluid>
            <nuxt />
        </v-container>
    </v-main>
    <v-footer class="noPrint" :absolute="!fixed" app>
        <small>&copy; {{ new Date().getFullYear() }} Developed by VG - Project
            team</small>
            <v-spacer></v-spacer>
        </v-footer>
    </v-app>
    <AuthComp ref="authComp" app="mpr" @setUser="setUser" />
</div>
</template>

<script>
// import AuthComp from "../../../@global-component/auth-comp";
import AuthComp from 'D:/source/@global-component/auth-comp.vue';
export default {
    components: {
        AuthComp,
    },
    data() {
        return {
            apiGlobalUser: "http://gmo021.cansportsvg.com/api/global-user/",
            storageUrl: 'http://gmo021.cansportsvg.com/api/',
            tokenApi: 'vg-recuitingReport/getphoto/epm/',
            loaded: false,
            clipped: false,
            drawer: false,
            fixed: false,
            miniVariant: false,
            right: true,
            rightDrawer: false,
            title: this.$t("appname"),
            activeUser: {},
            selectedCompany: null,
            userCompanies: [],
            companies: [
            { code: 'vg', label: 'VG' },
            { code: 'aw', label: 'AW' }
            ],
            isCompaniesLoaded: false,
        };
    },
    computed: {
        items() {
            const user = this.$session.get("mpr");
            const baseItems = [
            {
                icon: "mdi-view-dashboard",
                title: "Main page - User",
                to: "/dashboard_user",
                target: "_blank",
                
            },
            {
                icon: "mdi-information-variant",
                title: "User manual",
                href: "http://gmo021.cansportsvg.com/shared/user-manual/mpr/mpr.pdf",
                target: "_blank",
            },
            {
                icon: "mdi-home-export-outline",
                title: "Go home",
                href: "http://gmo021.cansportsvg.com/"
            }
            ];
            
            if (user && ['admin', 'review'].includes(user.role)) {
                return [
                {
                    icon: "mdi-home",
                    title: "Main page - Admin",
                    to: "/",
                },
                ...baseItems
                ];
            }
            return baseItems;
        }
    },
    methods: {
        selectCompany(company) {
            this.selectedCompany = company;
            this.$root.$emit('company-changed', company);
        },
        setUser(user) {
            const authArr = ['admin', 'review'];
            const appRoles = JSON.parse(user.app_roles);
            const roleItem = appRoles.find(app => app.app === "mpr");
            const role = roleItem ? roleItem.role : null;
            this.$session.set("mpr", {
                id: user.id,
                empno: user.empno,
                name: user.name,
                username: user.username,
                dept: user.dept,
                unit_name: user.name,
                hight_dept: user.high_dept,
                location: user.location,
                email: user.email,
                role: role,
            });
            
            if (roleItem && authArr.includes(roleItem.role)) {
                this.$router.push({
                    path: "/",
                })
            } else {
                this.$router.push({
                    path: "/dashboard_user",
                });
            }
            
            this.loaded = true
        },
        
        locale(tg) {
            this.$i18n.setLocale(tg);
            this.$root.$emit('change-locale', tg);
            this.$vuetify.lang.current = tg;
        },
        async checkAppAccess(empno) {
            const currentAppId = 49;
            
            try {
                const res = await this.$axios.post(this.apiGlobalUser + "checkAppAccess", { empno, app_id: currentAppId });
                if (res.status === 200 && res.data.status) {
                    if (res.data.data.length === 0) {
                        alert(`${this.$i18n.t('accessDenied', 'en')}\n ${this.$i18n.t('accessDenied', 'cn')}\n ${this.$i18n.t('accessDenied', 'vi')}`);
                        window.location.href = "http://gmo021.cansportsvg.com";
                    }
                    
                    this.userCompanies = res.data.data.map(item => item.company.code);
                    
                    if (!this.selectedCompany && this.userCompanies.length > 0) {
                        this.selectCompany(this.userCompanies[0]);
                    }
                }
            } catch (err) {
                console.error(err);
            }
        },
        locale(tg) {
            this.$i18n.setLocale(tg);
            // đảm bảo emit là string
            const payload = typeof tg === 'string' ? tg : String(tg);
            this.$root.$emit('change-locale', payload);
            this.$vuetify.lang.current = tg;
        }
        
    },
    provide() {
        return {
            selectedCompanyValue: () => this.selectedCompany
        }
    },
    watch: {
        userCompanies(newVal) {
            if (newVal.length > 0 && !this.selectedCompany) {
                this.selectCompany(newVal[0]);
            }
            this.isCompaniesLoaded = true;
        }
    },
    beforeDestroy() {
        // Cleanup listener khi component bị hủy
        this.$root.$off('change-locale');
    },
    mounted() {
        
        this.$root.$on('change-locale', (locale) => {
            this.$i18n.setLocale(locale);
            this.$vuetify.lang.current = locale;
        });
        if (this.$session.has("mpr")) {
            this.activeUser = this.$session.get("mpr");
            if (this.activeUser) {
                this.loaded = true;
                this.checkAppAccess(this.activeUser.empno);
                
                
                // // Dify chatbot integration
                // const mpr = this.$session.get('mpr') || {};
                // const photoUrl = mpr.empno ? `${this.storageUrl}${this.tokenApi}${mpr.empno}` : null;
                
                // window.difyChatbotConfig = {
                //     token: 'RXiPyzrCVrJlGqWA',
                //     baseUrl: 'http://10.13.34.176',
                //     dynamicScript: true,
                //     inputs: {
                //         username: mpr.name || 'Guest',
                //         lang: mpr.language || 'en',
                //     },
                //     systemVariables: {},
                //     userVariables: {
                //         empno: mpr.empno || '',
                //         name: mpr.name || '',
                //         avatar_url: photoUrl, 
                //     },
                // };
                // // Load script
                // const script = document.createElement('script');
                // script.src = 'http://10.13.34.176/embed.min.js';
                // script.id = 'RXiPyzrCVrJlGqWA';
                // script.defer = true;
                
                // document.body.appendChild(script);
                
            } else {
                this.loaded = false;
            }
        } else {
            this.$router.push({
                path: "/",
            });
        }
    },
};
</script>

<style>
/* #dify-chatbot-bubble-button {
    background-color: #1C64F2 !important;
}

#dify-chatbot-bubble-window {
    width: 24rem !important;
    height: 40rem !important;
} */
</style>
