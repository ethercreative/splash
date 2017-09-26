/* globals Craft */
import "./polyfills";
import { debounce, t, inViewport } from "./helpers";

const slowLoading = [
	"Any second now...",
	"Almost there...",
	"Just a moment...",
	"Nearly got it...",
	"Still searching for {query}...",
	"So... {query}, huh?",
	"{query} images coming right up...",
];

const noResults = [
	"We couldn't find anything for {query}",
	"We looked, but couldn't find any {query} images",
	"Are you sure {query} is a thing? We couldn't find it 😔",
];

class Splash { // eslint-disable-line no-unused-vars
	
	// Variables
	// =========================================================================
	
	grid = null;
	form = null;
	more = null;
	empty = null;
	
	io = null;
	xhr = null;
	
	page = 1;
	search = "";
	totalPages = 1;
	isQuerying = false;
	
	shortest = [];
	watchers = [];
	
	preview = null;
	previewHiRes = null;
	
	// Splash
	// =========================================================================
	
	constructor () {
		this.grid = document.getElementById("splashGrid");
		this.form = document.getElementById("splashSearch");
		this.more = document.getElementById("splashMore");
		this.empty = document.getElementById("splashEmpty");
		
		this.io = new IntersectionObserver(this.onObserve);
		this.io.observe(this.more);
		
		this.form.addEventListener("submit", e => e.preventDefault());
		this.form.firstElementChild.addEventListener(
			"input",
			debounce(this.onSearch, 700)
		);
		
		this.query(true);
		
		this.preview = t("div", { class: "splash--preview" });
		document.body.appendChild(this.preview);
	}
	
	// Actions
	// =========================================================================
	
	query (isNewSearch = false, isRetry = false) {
		if (isNewSearch) {
			this.page = 1;
			this.totalPages = 1;
			this.grid.classList.add("searching");
		} else if (!isRetry) this.page++;
		
		if (this.page > this.totalPages) return;
		
		this.isQuerying = true;
		
		this.xhr && this.xhr.cancel();
		
		this.xhr = new XMLHttpRequest();
		this.xhr.open("POST", Craft.getActionUrl("splash/un"), true);
		this.xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
		this.xhr.onload = () => {
			const status = this.xhr.status;
			let res = this.xhr.responseText;
			this.xhr = null;
			
			isNewSearch && this.grid.classList.remove("searching");
			this.isQuerying = false;
			
			if (status < 200 || status >= 400) {
				Craft.cp.displayError(res);
				return;
			}
			
			res = JSON.parse(res);
			
			// In case the Unsplash API decides to nope out
			if (res.images === null) {
				this.grid.dataset.loading = isRetry ? (
					slowLoading[Math.floor(Math.random() * slowLoading.length)]
						.replace("{query}", this.search)
				) : "Unsplash is taking a while...";
				this.query(isNewSearch, true);
				return;
			}
			
			this.totalPages = res.totalPages;
			this.populateResults(res.images);
		};
		
		const data = new FormData();
		data.append(Craft.csrfTokenName, Craft.csrfTokenValue);
		data.append("page", this.page);
		data.append("query", encodeURI(this.search.trim()));
		
		this.xhr.send(data);
	}
	
	populateResults (results) {
		if (this.page === 1) {
			this.clearResults();
			this.resetShortest();
		}
		
		results.forEach(result => {
			let height = 75;
			
			if (result.width && result.height) {
				height = (result.height / result.width) * 100;
			}
			
			const si = this.shortest.indexOf(Math.min(...this.shortest));
			this.shortest[si] += height;
			
			this.grid.children[si].appendChild(
				this.render(result)
			);
		});
		
		this.watchers.forEach(watcher => this.loadNextImage(watcher));
		
		if (this.page === this.totalPages || this.totalPages === 0)
			this.more.classList.add("hide");
		
		if (this.totalPages === 0) {
			this.empty.textContent =
				noResults[Math.floor(Math.random() * noResults.length)]
					.replace("{query}", this.search);
			this.empty.classList.add("show");
		}
	}
	
	showPreview (image) {
		while (this.preview.firstElementChild)
			this.preview.removeChild(this.preview.firstElementChild);
		
		this.preview.appendChild(
			this.render(image, () => {
				this.preview.classList.remove("open");
				document.body.style.overflow = "";
				document.body.parentNode.style.overflow = "";
			}),
		);
		
		this.preview.classList.add("open");
		document.body.style.overflow = "hidden";
		document.body.parentNode.style.overflow = "hidden";
		
		setTimeout(() => {
			this.previewHiRes.setAttribute("src", this.previewHiRes.dataset.src);
		}, 500);
	}
	
	// Events
	// =========================================================================
	
	onSearch = e => {
		this.search = e.target.value;
		this.query(true);
	};
	
	onLoad = e => {
		e.target.removeEventListener("load", this.onLoad);
		e.target.parentNode.parentNode.classList.add("loaded");
		e.target.style.paddingTop = "0";
	};
	
	onObserve = entries => {
		entries.forEach(entry => {
			if (!entry.isIntersecting) {
				if (entry.target.dataset.watcher
				    && entry.boundingClientRect.top <= 0)
					this.loadNextImage(entry.target);
				return;
			}
			
			if (entry.target.id === "splashMore") {
				if (this.page !== this.totalPages && !this.isQuerying)
					this.query();
				return;
			}
			
			if (entry.target.dataset.watcher) {
				this.loadNextImage(entry.target);
			}
		});
	};
	
	onDownload = (e, target = null) => {
		e.preventDefault();
		target = target ? target : e.target;
		const { id, image, author, authorUrl, color } = target.dataset;
		
		let parent = target;
		while (!parent.classList.contains("splash--grid-image"))
			parent = parent.parentNode;
		
		parent.classList.add("downloading");
		
		Craft.postActionRequest("splash/dl", {
			id, image, author, authorUrl, color,
			query: this.search,
		}, (res, status) => {
			parent.classList.remove("downloading");
			
			if (status !== "success" || res.hasOwnProperty("error")) {
				Craft.cp.displayError(
					res.hasOwnProperty("error")
						? res.error : "Failed to download image."
				);
				return;
			}
			
			Craft.cp.displayNotice("Image downloaded successfully!");
		});
	};
	
	// Helpers
	// =========================================================================
	
	clearResults () {
		const c = this.grid.children;
		
		for (let i = 0; i < c.length; i++) {
			while (c[i].firstElementChild)
				c[i].removeChild(c[i].firstElementChild);
		
			if (!this.watchers[i]) {
				this.watchers[i] = t("span");
				this.watchers[i].dataset.watcher = true;
				this.io.observe(this.watchers[i]);
			}
			
			c[i].appendChild(this.watchers[i]);
		}
		
		this.more.classList.remove("hide");
		this.empty.classList.remove("show");
	}
	
	resetShortest () {
		this.shortest = [];
		
		for (let i = 0; i < this.grid.children.length; i++)
			this.shortest.push(0);
	}
	
	loadNextImage (target) {
		const next = target.nextElementSibling;
		
		if (!next || !next.classList.contains("splash--grid-image"))
			return;
		
		const img = next.querySelector("img");
		img.setAttribute("src", img.dataset.src);
		img.setAttribute("alt", img.dataset.alt);
		
		target.parentNode.insertBefore(target, next.nextElementSibling);
		
		inViewport(target) && this.loadNextImage(target);
	}
	
	render (
		{ id, urls, user, width, height, links, color },
		onClick = null
	) {
		let padTop = 75;
		if (width && height) {
			padTop = (height / width) * 100;
		}
		
		const isPreview = onClick !== null;
		if (!onClick) onClick = this.showPreview.bind(this, arguments[0]);
		
		const refer = "?utm_source=Splash_For_Craft_CMS&utm_medium=referral&utm_campaign=api-credit";
		
		return t("div", {
			class: "splash--grid-image" + (isPreview ? " loaded" : ""),
			style: isPreview ? "" : `
				padding-top: ${padTop}%;
			`,
		}, [
			t("div", { class: "splash--grid-image-top" }, [
				t("a", {
					href: user.links.html + refer,
					target: "_blank",
				}, user.name),
				t("a", {
					id: (isPreview ? "" : id),
					class: "dl",
					href: links.download + refer,
					target: "_blank",
					"data-id": id,
					"data-image": links.download,
					"data-author": user.name,
					"data-author-url": user.links.html,
					"data-color": color,
					click: isPreview ? e => {
						onClick(e);
						this.onDownload(e, document.getElementById(id));
					} : this.onDownload,
				}, "Download"),
			]),
			t("div", {
				class: "splash--grid-image-img" + (
					width && height && width > height ? " wide" : " tall"
				)
			}, [
				t("img", {
					...(isPreview ? {
						src: urls.small,
						alt: user.name,
					} : {
						"data-src": urls.small,
						"data-alt": user.name,
					}),
					load: this.onLoad,
					click: onClick,
				}),
				...(isPreview ? [t("img", {
					"data-src": urls.full,
					alt: user.name,
					click: onClick,
					ref: el => { this.previewHiRes = el; }
				})] : []),
			])
		]);
	}
	
}
